<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\ExportService;
use App\Support\Audit;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, ExportService $exports)
    {
        $periods = PayrollPeriod::newestFirst()->get();
        $period = $periods->firstWhere('id', (int) $request->query('period')) ?? $periods->first();
        $records = $period ? $exports->records($period, $this->status($request), $this->payment($request)) : collect();

        return Inertia::render('admin/Reports', [
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'period' => $period ? PayrollPresenter::period($period) : null,
            'filters' => ['status' => $request->query('status', ''), 'payment' => $request->query('payment', '')],
            'summary' => $exports->summary($records),
            'rows' => $records->take(200)->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->employee->full_name, 'nip' => $r->employee->nip, 'status' => $r->employee->employment_status->value,
                'gross' => Money::str($r->gross_salary), 'net' => Money::str($r->net_salary), 'school' => Money::str($r->school_deductions_total), 'take_home' => Money::str($r->take_home_pay),
                'payment_label' => ($r->payment?->status ?? PaymentStatus::Unpaid)->label(),
            ])->values(),
            'truncated' => $records->count() > 200,
        ]);
    }

    public function export(Request $request, ExportService $exports)
    {
        $data = $request->validate(['period' => ['required', 'exists:payroll_periods,id'], 'type' => ['required', 'in:full,deductions,payments']]);
        $period = PayrollPeriod::findOrFail($data['period']);
        $records = $exports->records($period, $this->status($request), $this->payment($request));
        $export = match ($data['type']) {
            'full' => $exports->full($period, $records), 'deductions' => $exports->deductions($period, $records), 'payments' => $exports->payments($period, $records),
        };
        Audit::log('report.export', $period, null, ['jenis' => $data['type'], 'baris' => $records->count()]);

        return Excel::download($export, Str::slug($data['type'].'-'.$period->label).'.xlsx');
    }

    private function status(Request $r): ?string
    {
        return in_array($r->query('status'), ['PNS', 'PPPK'], true) ? $r->query('status') : null;
    }

    private function payment(Request $r): ?string
    {
        return PaymentStatus::tryFrom((string) $r->query('payment'))?->value;
    }
}
