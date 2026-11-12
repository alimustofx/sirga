<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PeriodDeductionConfig;
use App\Models\SchoolDeductionType;
use App\Services\Payroll\DeductionService;
use App\Support\Audit;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeductionController extends Controller
{
    private const AMOUNT = ['required', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'];

    public function index(Request $request)
    {
        $periods = PayrollPeriod::newestFirst()->get();
        $period = $periods->firstWhere('id', (int) $request->query('period')) ?? $periods->first();
        $configs = $period ? $period->deductionConfigs()->with('type')->get() : collect();
        $perPage = in_array((int) $request->query('per_page'), [10, 15, 25, 50], true) ? (int) $request->query('per_page') : 15;
        $rows = new LengthAwarePaginator([], 0, $perPage);
        $configTotals = [];

        if ($period) {
            $q = PayrollRecord::query()->select('payroll_records.*')
                ->join('employees', 'employees.id', '=', 'payroll_records.employee_id')
                ->where('payroll_records.payroll_period_id', $period->id)
                ->with(['employee', 'deductions']);
            if ($s = trim((string) $request->query('q'))) {
                $q->where(fn ($w) => $w->where('employees.full_name', 'like', "%{$s}%")->orWhere('employees.nip', 'like', '%'.preg_replace('/\s+/', '', $s).'%'));
            }
            if (in_array($request->query('status'), ['PNS', 'PPPK'], true)) {
                $q->where('employees.employment_status', $request->query('status'));
            }
            $rows = $q->orderBy('employees.full_name')->paginate($perPage)->withQueryString()->through(fn ($r) => [
                'id' => $r->id, 'name' => $r->employee->full_name, 'nip' => $r->employee->nip, 'status' => $r->employee->employment_status->value,
                'deductions' => $r->deductions->map(fn ($d) => ['id' => $d->id, 'config_id' => $d->period_deduction_config_id, 'name' => $d->deduction_name_snapshot, 'amount' => Money::str($d->amount)])->values(),
                'total' => Money::str($r->school_deductions_total), 'take_home' => Money::str($r->take_home_pay),
            ]);

            // total per jenis untuk seluruh periode (bukan hanya halaman ini)
            $sums = PayrollDeduction::query()->selectRaw('period_deduction_config_id, sum(amount) as total')
                ->whereIn('period_deduction_config_id', $configs->pluck('id'))->groupBy('period_deduction_config_id')->pluck('total', 'period_deduction_config_id');
            foreach ($configs as $c) {
                $configTotals[$c->id] = Money::str($sums[$c->id] ?? 0);
            }
        }

        return Inertia::render('admin/Deductions', [
            'types' => SchoolDeductionType::orderBy('display_order')->orderBy('id')->get()->map(fn ($t) => [
                'id' => $t->id, 'name' => $t->name, 'description' => $t->description, 'default_amount' => Money::str($t->default_amount),
                'is_recurring' => $t->is_recurring, 'is_active' => $t->is_active, 'display_order' => $t->display_order,
            ]),
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'period' => $period ? PayrollPresenter::period($period) : null,
            'configs' => $configs->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'description' => $c->description, 'default_amount' => Money::str($c->default_amount), 'is_special' => $c->is_special, 'master_default' => $c->type ? Money::str($c->type->default_amount) : null]),
            'rows' => $rows,
            'perPage' => $perPage,
            'configTotals' => $configTotals,
            'filters' => ['q' => $request->query('q', ''), 'status' => $request->query('status', '')],
            'needsReason' => $period?->status->value === 'published',
            'locked' => $period?->status->value === 'closed',
        ]);
    }

    // ---------- Master jenis potongan ----------
    public function storeType(Request $request)
    {
        $t = SchoolDeductionType::create($this->typeData($request) + ['display_order' => (int) SchoolDeductionType::max('display_order') + 1]);
        Audit::log('deduction_type.create', $t, null, $t->only(['name', 'default_amount']));
        $this->toast('success', 'Jenis potongan ditambahkan. Berlaku untuk periode baru; periode lama tidak berubah.');

        return back();
    }

    public function updateType(Request $request, SchoolDeductionType $type)
    {
        $old = $type->only(['name', 'default_amount', 'is_recurring', 'is_active']);
        $type->update($this->typeData($request));
        Audit::log('deduction_type.update', $type, $old, $type->only(array_keys($old)));
        $this->toast('success', 'Jenis potongan disimpan. Periode yang sudah dibuat tidak berubah.');

        return back();
    }

    public function destroyType(SchoolDeductionType $type)
    {
        if (PeriodDeductionConfig::where('school_deduction_type_id', $type->id)->exists()) {
            $this->toast('error', 'Jenis potongan sudah dipakai pada periode. Nonaktifkan saja.');

            return back();
        }
        Audit::log('deduction_type.delete', $type, $type->only(['name']));
        $type->delete();
        $this->toast('success', 'Jenis potongan dihapus.');

        return back();
    }

    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            SchoolDeductionType::whereKey($id)->update(['display_order' => $i + 1]);
        }
        Audit::log('deduction_type.reorder', null, null, ['urutan' => $ids]);

        return back();
    }

    // ---------- Potongan per periode ----------
    public function addConfig(Request $request, PayrollPeriod $period, DeductionService $service)
    {
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:255'], 'amount' => self::AMOUNT]);

        return $this->attempt(fn () => $service->addConfig($period, $d['name'], $d['description'] ?? null, $d['amount'], $request->user()), 'Jenis potongan ditambahkan pada periode ini.');
    }

    public function removeConfig(Request $request, PeriodDeductionConfig $config, DeductionService $service)
    {
        return $this->attempt(fn () => $service->removeConfig($config, $request->user()), 'Jenis potongan dihapus dari periode.');
    }

    public function mass(Request $request, PeriodDeductionConfig $config, DeductionService $service)
    {
        $d = $request->validate(['amount' => self::AMOUNT, 'scope' => ['required', Rule::in(['all', 'PNS', 'PPPK'])], 'reason' => ['nullable', 'string', 'max:500']]);

        return $this->attempt(fn () => $count = $service->massUpdate($config->period, $config, $d['amount'], $d['scope'], $d['reason'] ?? null, $request->user()), 'Nominal potongan diperbarui untuk ASN terpilih.');
    }

    public function record(Request $request, PayrollRecord $record, DeductionService $service)
    {
        $d = $request->validate(['amounts' => ['required', 'array'], 'amounts.*' => self::AMOUNT, 'reason' => ['nullable', 'string', 'max:500']]);

        return $this->attempt(fn () => $service->setForRecord($record, $d['amounts'], $d['reason'] ?? null, $request->user()), 'Potongan ASN disimpan.');
    }

    public function copyPrevious(Request $request, PayrollPeriod $period, DeductionService $service)
    {
        return $this->attempt(fn () => $service->copyPrevious($period, $request->user()), 'Potongan periode sebelumnya berhasil disalin.');
    }

    private function typeData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:255'],
            'default_amount' => self::AMOUNT, 'is_recurring' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
        ]);
    }
}
