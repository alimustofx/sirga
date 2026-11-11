<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollPeriodService;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodController extends Controller
{
    public function index()
    {
        $employees = Employee::active()->count();
        $periods = PayrollPeriod::newestFirst()->withCount('records')->get();

        return Inertia::render('admin/Periods', [
            'periods' => $periods->map(fn ($p) => [
                ...PayrollPresenter::period($p), 'records' => $p->records_count, 'employees' => $employees,
                'completeness' => $employees > 0 ? (int) round(min($p->records_count, $employees) / $employees * 100) : 0,
                'published_at' => $p->published_at?->toIso8601String(),
            ]),
            'defaults' => ['month' => (int) now(config('sirga.timezone'))->month, 'year' => (int) now(config('sirga.timezone'))->year],
        ]);
    }

    public function store(Request $request, PayrollPeriodService $service)
    {
        $data = $this->validatedPeriod($request);

        return $this->attempt(
            fn () => $service->create($data['month'], $data['year'], $data['notes'] ?? null, $request->user()),
            'Periode berhasil dibuat.', fn ($p) => redirect('/admin/periode/'.$p->id),
        );
    }

    public function duplicate(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        $data = $this->validatedPeriod($request);

        return $this->attempt(
            fn () => $service->duplicate($period, $data['month'], $data['year'], $request->user()),
            'Periode digandakan. Seluruh rincian perlu diverifikasi sebelum publikasi.', fn ($p) => redirect('/admin/periode/'.$p->id),
        );
    }

    public function show(PayrollPeriod $period, PayrollPeriodService $service)
    {
        $period->loadCount('records');
        $readiness = $service->readiness($period);

        return Inertia::render('admin/PeriodShow', [
            'period' => [...PayrollPresenter::period($period), 'notes' => $period->notes, 'published_at' => $period->published_at?->toIso8601String(), 'closed_at' => $period->closed_at?->toIso8601String(), 'records' => $period->records_count],
            'readiness' => $readiness,
            'totals' => [
                'gross' => (string) Money::sum($period->records()->pluck('gross_salary')), 'official' => (string) Money::sum($period->records()->pluck('official_deductions_total')),
                'net' => (string) Money::sum($period->records()->pluck('net_salary')), 'school' => (string) Money::sum($period->records()->pluck('school_deductions_total')),
                'take_home' => (string) Money::sum($period->records()->pluck('take_home_pay')),
            ],
            'batches' => $period->importBatches()->with('period')->latest('id')->limit(8)->get()->map(fn ($b) => [
                'id' => $b->id, 'filename' => $b->original_filename, 'status' => $b->employment_status, 'state' => $b->import_status,
                'total' => $b->total_rows, 'valid' => $b->valid_rows, 'invalid' => $b->invalid_rows, 'at' => $b->created_at->toIso8601String(),
            ]),
            'configs' => $period->deductionConfigs()->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'amount' => Money::str($c->default_amount)]),
            'canDelete' => $period->status === PeriodStatus::Draft && $period->records_count === 0,
        ]);
    }

    public function review(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        return $this->attempt(fn () => $service->toReview($period, $request->user()), 'Periode dipindahkan ke Review.');
    }

    public function draft(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        return $this->attempt(fn () => $service->backToDraft($period, $request->user()), 'Periode dikembalikan ke Draft.');
    }

    public function publish(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        return $this->attempt(fn () => $service->publish($period, $request->user()), 'Periode dipublikasikan. Guru dapat melihat rincian gaji.');
    }

    public function close(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        return $this->attempt(fn () => $service->close($period, $request->user()), 'Periode ditutup.');
    }

    public function destroy(Request $request, PayrollPeriod $period, PayrollPeriodService $service)
    {
        return $this->attempt(fn () => $service->destroy($period, $request->user()), 'Periode dihapus.', fn () => redirect('/admin/periode'));
    }

    private function validatedPeriod(Request $request): array
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        if (PayrollPeriod::where('period_month', $data['month'])->where('period_year', $data['year'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['month' => 'Periode tersebut sudah ada.']);
        }

        return $data;
    }
}
