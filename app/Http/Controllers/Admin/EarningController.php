<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComponentType;
use App\Enums\ValidationStatus;
use App\Http\Controllers\Controller;
use App\Models\EarningComponent;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollRecordService;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EarningController extends Controller
{
    public function index(Request $request)
    {
        $periods = PayrollPeriod::newestFirst()->get();
        $period = $periods->firstWhere('id', (int) $request->query('period')) ?? $periods->first();
        $perPage = in_array((int) $request->query('per_page'), [10, 15, 25, 50], true) ? (int) $request->query('per_page') : 15;
        $records = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        $missing = collect();

        if ($period) {
            $q = PayrollRecord::query()->select('payroll_records.*')
                ->join('employees', 'employees.id', '=', 'payroll_records.employee_id')
                ->where('payroll_records.payroll_period_id', $period->id)->with('employee');
            if ($s = trim((string) $request->query('q'))) {
                $q->where(fn ($w) => $w->where('employees.full_name', 'like', "%{$s}%")->orWhere('employees.nip', 'like', '%'.preg_replace('/\s+/', '', $s).'%'));
            }
            if (in_array($request->query('status'), ['PNS', 'PPPK'], true)) {
                $q->where('employees.employment_status', $request->query('status'));
            }
            if ($v = $request->query('validation')) {
                $q->where('payroll_records.validation_status', $v);
            }
            $records = $q->orderBy('employees.full_name')->paginate($perPage)->withQueryString()->through(fn ($r) => [
                'id' => $r->id, 'name' => $r->employee->full_name, 'nip' => $r->employee->nip, 'status' => $r->employee->employment_status->value,
                'gross' => Money::str($r->gross_salary), 'official' => Money::str($r->official_deductions_total), 'net' => Money::str($r->net_salary),
                'school' => Money::str($r->school_deductions_total), 'take_home' => Money::str($r->take_home_pay),
                'validation' => $r->validation_status->value, 'validation_label' => $r->validation_status->label(),
            ]);
            $missing = Employee::active()->whereDoesntHave('payrollRecords', fn ($w) => $w->where('payroll_period_id', $period->id))->orderBy('full_name')->get(['id', 'full_name', 'nip']);
        }

        return Inertia::render('admin/Earnings', [
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'period' => $period ? PayrollPresenter::period($period) : null,
            'records' => $records,
            'perPage' => $perPage,
            'missing' => $missing,
            'filters' => ['q' => $request->query('q', ''), 'status' => $request->query('status', ''), 'validation' => $request->query('validation', '')],
            'counts' => $period ? ['copied' => $period->records()->where('validation_status', ValidationStatus::Copied->value)->count()] : ['copied' => 0],
        ]);
    }

    public function show(PayrollRecord $record)
    {
        $record->load(['period', 'employee', 'items', 'deductions', 'revisions.author']);
        $present = $record->items->pluck('earning_component_id')->filter()->all();
        $available = EarningComponent::where('is_active', true)->whereIn('applicable_status', ['all', $record->employee->employment_status->value])
            ->whereNotIn('id', $present)->orderBy('component_type')->orderBy('display_order')->get(['id', 'name', 'component_type']);

        return Inertia::render('admin/RecordEdit', [
            'record' => [
                'id' => $record->id, 'period' => PayrollPresenter::period($record->period),
                'employee' => ['name' => $record->employee->full_name, 'nip' => $record->employee->nip, 'status' => $record->employee->employment_status->value],
                'validation' => $record->validation_status->value, 'validation_label' => $record->validation_status->label(), 'note' => $record->validation_note,
                'source' => ['gross' => $record->source_gross, 'official' => $record->source_official_deductions, 'net' => $record->source_net],
                'totals' => ['gross' => Money::str($record->gross_salary), 'official' => Money::str($record->official_deductions_total), 'net' => Money::str($record->net_salary), 'school' => Money::str($record->school_deductions_total), 'take_home' => Money::str($record->take_home_pay)],
                'locked' => $record->period->status->value === 'closed',
                'needs_reason' => $record->period->status->value === 'published',
            ],
            'items' => $record->items->map(fn ($i) => ['id' => $i->id, 'component_id' => $i->earning_component_id, 'name' => $i->component_name_snapshot, 'type' => $i->component_type->value, 'amount' => Money::str($i->amount), 'source' => $i->source_value ? Money::str($i->source_value) : null])->values(),
            'deductions' => $record->deductions->map(fn ($d) => ['id' => $d->id, 'name' => $d->deduction_name_snapshot, 'amount' => Money::str($d->amount)])->values(),
            'available' => $available->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->component_type->value])->values(),
            'revisions' => $record->revisions->take(10)->map(fn ($r) => ['version' => $r->version, 'reason' => $r->reason, 'by' => $r->author?->name, 'at' => $r->created_at?->toIso8601String(), 'take_home' => $r->snapshot['total']['diterimakan'] ?? null])->values(),
        ]);
    }

    public function update(Request $request, PayrollRecord $record, PayrollRecordService $service)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'], 'items.*.component_id' => ['nullable', 'integer'],
            'items.*.type' => ['required', Rule::enum(ComponentType::class)],
            'items.*.amount' => ['required', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'],
            'deductions' => ['nullable', 'array'], 'deductions.*' => ['nullable', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], ['items.*.amount.regex' => 'Nominal harus berupa angka positif (maksimal 2 desimal).', 'deductions.*.regex' => 'Nominal potongan harus berupa angka positif.']);

        return $this->attempt(
            fn () => $service->update($record, $data['items'], $data['deductions'] ?? [], $data['reason'] ?? null, $request->user()),
            'Rincian gaji berhasil disimpan.',
        );
    }

    public function store(Request $request, PayrollRecordService $service)
    {
        $data = $request->validate(['period_id' => ['required', 'exists:payroll_periods,id'], 'employee_id' => ['required', 'exists:employees,id']]);

        return $this->attempt(
            fn () => $service->createFor(PayrollPeriod::findOrFail($data['period_id']), Employee::findOrFail($data['employee_id']), $request->user()),
            'Rincian dibuat. Silakan isi nominal penghasilan.',
            fn ($record) => redirect('/admin/penghasilan/'.$record->id),
        );
    }

    public function verify(Request $request, PayrollRecord $record, PayrollRecordService $service)
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        return $this->attempt(fn () => $service->markVerified($record, $note, $request->user()), 'Rincian ditandai sudah diperiksa.');
    }

    public function verifyAll(Request $request, PayrollPeriod $period, PayrollRecordService $service)
    {
        return $this->attempt(fn () => $service->verifyAll($period, $request->user()), 'Seluruh rincian hasil duplikasi telah ditandai diperiksa.');
    }
}
