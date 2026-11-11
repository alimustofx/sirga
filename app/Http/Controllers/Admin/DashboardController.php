<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollPeriodService;
use App\Support\AuditLabels;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, PayrollPeriodService $periods)
    {
        $period = PayrollPeriod::newestFirst()->where('status', '!=', 'closed')->first() ?? PayrollPeriod::newestFirst()->first();
        $readiness = $period ? $periods->readiness($period) : null;
        $active = Employee::active();

        $logs = AuditLog::with('user')->latest('id')->limit(40)->get()
            ->filter(fn ($l) => $request->user()->isSuperAdmin() || collect(AuditLabels::financePrefixes())->contains(fn ($p) => str_starts_with($l->action, $p)))
            ->take(8)->values();

        return Inertia::render('admin/Dashboard', [
            'cards' => [
                'total' => (clone $active)->count(),
                'pns' => (clone $active)->where('employment_status', 'PNS')->count(),
                'pppk' => (clone $active)->where('employment_status', 'PPPK')->count(),
                'incomplete' => $readiness ? $readiness['stats']['employees'] - $readiness['stats']['complete'] : 0,
                'pending_payments' => Payment::where('status', PaymentStatus::Pending->value)->count(),
            ],
            'period' => $period ? [...PayrollPresenter::period($period), 'complete' => $readiness['stats']['complete'], 'employees' => $readiness['stats']['employees'], 'issues' => count($readiness['issues'])] : null,
            'pending' => Payment::where('status', PaymentStatus::Pending->value)->with('record.employee', 'record.period')->latest('submitted_at')->limit(8)->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->record->employee->full_name, 'nip' => $p->record->employee->nip, 'period' => $p->record->period->label, 'amount' => Money::str($p->total_amount), 'submitted_at' => $p->submitted_at?->toIso8601String()]),
            'activity' => $logs->map(fn ($l) => ['id' => $l->id, 'label' => AuditLabels::label($l->action), 'user' => $l->user?->name ?? 'Sistem', 'at' => $l->created_at->toIso8601String()]),
        ]);
    }
}
