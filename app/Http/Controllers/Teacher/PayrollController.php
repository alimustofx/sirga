<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Support\Audit;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    /** Periode terbit milik pegawai yang sedang login (urut terbaru). */
    public static function visiblePeriods(Request $request)
    {
        $employee = $request->user()->employee;

        return PayrollPeriod::visibleToTeachers()->newestFirst()
            ->whereHas('records', fn ($q) => $q->where('employee_id', $employee->id))->get();
    }

    /** Rekaman milik pegawai login; guru tidak pernah memberi employee_id sendiri. */
    public static function recordFor(Request $request, PayrollPeriod $period): PayrollRecord
    {
        abort_unless($period->status->isVisibleToTeacher(), 404);

        return PayrollRecord::where('payroll_period_id', $period->id)
            ->where('employee_id', $request->user()->employee->id)->firstOrFail();
    }

    public function show(Request $request, ?PayrollPeriod $period = null)
    {
        $periods = self::visiblePeriods($request);
        $period ??= $periods->first();

        return Inertia::render('guru/Payroll', [
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'payroll' => $period ? PayrollPresenter::detail(self::recordFor($request, $period)) : null,
            'payment' => $period ? PayrollPresenter::payment(self::recordFor($request, $period)) : null,
        ]);
    }

    public function history(Request $request)
    {
        $records = PayrollRecord::where('employee_id', $request->user()->employee->id)
            ->whereHas('period', fn ($q) => $q->visibleToTeachers())->with(['period', 'payment'])->get()
            ->sortByDesc(fn ($r) => $r->period->period_year * 100 + $r->period->period_month)->values();

        return Inertia::render('guru/History', [
            'items' => $records->map(fn ($r) => [
                'period' => PayrollPresenter::period($r->period),
                'gross' => Money::str($r->gross_salary), 'net' => Money::str($r->net_salary),
                'school_total' => Money::str($r->school_deductions_total), 'take_home' => Money::str($r->take_home_pay),
                'payment_status' => $r->payment?->status->value ?? 'belum_dibayar',
                'payment_label' => $r->payment?->status->label() ?? 'Belum Dibayar',
                'has_payable' => ! Money::dec($r->school_deductions_total)->isZero(),
            ]),
        ]);
    }

    public function slip(Request $request, PayrollPeriod $period, \App\Services\PayslipService $slips)
    {
        $record = self::recordFor($request, $period);
        Audit::log('payslip.download', $record);

        return $slips->make($record)->download($slips->filename($record));
    }
}
