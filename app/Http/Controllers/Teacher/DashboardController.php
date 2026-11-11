<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        $records = PayrollRecord::where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q->visibleToTeachers())
            ->with('period')->get()
            ->sortByDesc(fn ($r) => $r->period->period_year * 100 + $r->period->period_month)->values();

        $latest = $records->first();

        return Inertia::render('guru/Dashboard', [
            'employee' => ['name' => $employee->full_name, 'status' => $employee->employment_status->value],
            'current' => $latest ? [
                'period' => PayrollPresenter::period($latest->period),
                'net' => Money::str($latest->net_salary),
                'school_total' => Money::str($latest->school_deductions_total),
                'take_home' => Money::str($latest->take_home_pay),
                'payment' => PayrollPresenter::payment($latest),
            ] : null,
            'recent' => $records->take(3)->map(fn ($r) => ['period_id' => $r->payroll_period_id, 'label' => $r->period->label, 'take_home' => Money::str($r->take_home_pay)])->values(),
            'announcements' => Announcement::visibleTo($employee)->orderByDesc('published_at')->limit(3)->get()
                ->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'excerpt' => str($a->content)->limit(140)->toString(), 'published_at' => $a->published_at->toIso8601String()]),
        ]);
    }
}
