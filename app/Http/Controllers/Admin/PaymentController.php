<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\PaymentController as TeacherPayment;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Services\PaymentService;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $periods = PayrollPeriod::visibleToTeachers()->newestFirst()->get();
        $period = $periods->firstWhere('id', (int) $request->query('period')) ?? $periods->first();
        $items = collect();
        $per = in_array((int) $request->query('per_page'), [10, 15, 25, 50], true) ? (int) $request->query('per_page') : 15;

        if ($period) {
            $q = PayrollRecord::where('payroll_period_id', $period->id)->where('school_deductions_total', '>', 0)->with(['employee', 'payment']);
            if ($s = trim((string) $request->query('q'))) {
                $q->whereHas('employee', fn ($w) => $w->where('full_name', 'like', "%{$s}%")->orWhere('nip', 'like', '%'.preg_replace('/\s+/', '', $s).'%'));
            }
            $items = $q->get()->map(fn ($r) => [
                'record_id' => $r->id, 'payment_id' => $r->payment?->id, 'name' => $r->employee->full_name, 'nip' => $r->employee->nip,
                'status' => ($r->payment?->status ?? PaymentStatus::Unpaid)->value, 'status_label' => ($r->payment?->status ?? PaymentStatus::Unpaid)->label(),
                'amount' => Money::str($r->school_deductions_total), 'submitted_at' => $r->payment?->submitted_at?->toIso8601String(), 'verified_at' => $r->payment?->verified_at?->toIso8601String(),
            ])->sortBy('name')->values();
        }
        $counts = collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => 0])->merge($items->countBy('status'))->all();
        if ($period && ($f = $request->query('status'))) {
            $items = $items->where('status', $f)->values();
        }

        $pageNo = max(1, (int) $request->query('page', 1));
        $paged = new LengthAwarePaginator($items->forPage($pageNo, $per)->values(), $items->count(), $per, $pageNo, ['path' => $request->url(), 'query' => $request->query()]);

        return Inertia::render('admin/Payments', [
            'perPage' => $per,
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'period' => $period ? PayrollPresenter::period($period) : null,
            'items' => $paged,
            'counts' => $counts,
            'filters' => ['q' => $request->query('q', ''), 'status' => $request->query('status', '')],
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['record.employee', 'record.period', 'proofs.verifier', 'verifier']);
        $record = $payment->record;
        $info = PayrollPresenter::payment($record, '/admin/bukti');

        return Inertia::render('admin/PaymentShow', [
            'payment' => [
                'id' => $payment->id, 'status' => $payment->status->value, 'status_label' => $payment->status->label(), 'total' => Money::str($payment->total_amount),
                'submitted_at' => $payment->submitted_at?->toIso8601String(), 'verified_at' => $payment->verified_at?->toIso8601String(),
                'verifier' => $payment->verifier?->name, 'rejection_reason' => $payment->rejection_reason,
            ],
            'employee' => ['name' => $record->employee->full_name, 'nip' => $record->employee->nip, 'status' => $record->employee->employment_status->value, 'account' => $record->employee->bank_account_number],
            'period' => PayrollPresenter::period($record->period),
            'proofs' => $info['proofs'], 'transfer_note' => $info['transfer_note'],
            'record_id' => $record->id,
            'canVerify' => $payment->status === PaymentStatus::Pending,
        ]);
    }

    public function approve(Request $request, Payment $payment, PaymentService $service)
    {
        return $this->attempt(fn () => $service->approve($payment, $request->user()), 'Pembayaran disetujui dan ditandai Lunas.');
    }

    public function reject(Request $request, Payment $payment, PaymentService $service)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], ['reason.required' => 'Alasan penolakan wajib diisi.', 'reason.min' => 'Jelaskan alasan penolakan dengan lebih jelas.'])['reason'];

        return $this->attempt(fn () => $service->reject($payment, $reason, $request->user()), 'Bukti pembayaran ditolak. Guru dapat mengunggah ulang.');
    }

    public function proof(PaymentProof $proof)
    {
        return TeacherPayment::fileResponse($proof);
    }
}
