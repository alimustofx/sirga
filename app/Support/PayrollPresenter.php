<?php

namespace App\Support;

use App\Enums\ComponentType;
use App\Enums\PaymentStatus;
use App\Enums\PeriodStatus;
use App\Models\PaymentAccount;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SchoolSetting;

/** Membentuk data yang dikirim ke frontend (hanya field yang perlu ditampilkan). */
final class PayrollPresenter
{
    public static function period(PayrollPeriod $p): array
    {
        return ['id' => $p->id, 'label' => $p->label, 'month' => $p->period_month, 'year' => $p->period_year, 'status' => $p->status->value, 'status_label' => $p->status->label()];
    }

    public static function detail(PayrollRecord $r): array
    {
        $r->loadMissing(['period', 'employee', 'items', 'deductions']);
        $setting = SchoolSetting::current();
        $nonZero = fn ($i) => ! Money::dec($i->amount)->isZero();
        $line = fn ($name, $amount) => ['name' => $name, 'amount' => Money::str($amount)];
        $e = $r->employee;

        return [
            'id' => $r->id,
            'period' => self::period($r->period),
            'employee' => [
                'name' => $e->full_name, 'nip' => $e->nip, 'status' => $e->employment_status->value,
                'rank' => $e->rank, 'position' => $e->position, 'unit' => $e->unit, 'account' => $e->bank_account_number,
            ],
            'earnings' => $r->items->filter(fn ($i) => $i->component_type === ComponentType::Earning && $nonZero($i))->map(fn ($i) => $line($i->component_name_snapshot, $i->amount))->values(),
            'official' => $r->items->filter(fn ($i) => $i->component_type === ComponentType::OfficialDeduction && $nonZero($i))->map(fn ($i) => $line($i->component_name_snapshot, $i->amount))->values(),
            'deductions' => $r->deductions->filter(fn ($d) => $setting->show_zero_deductions || $nonZero($d))->map(fn ($d) => $line($d->deduction_name_snapshot, $d->amount))->values(),
            'gross' => Money::str($r->gross_salary),
            'official_total' => Money::str($r->official_deductions_total),
            'net' => Money::str($r->net_salary),
            'school_total' => Money::str($r->school_deductions_total),
            'take_home' => Money::str($r->take_home_pay),
        ];
    }

    public static function payment(PayrollRecord $r, string $proofBase = '/saya/bukti'): array
    {
        $r->loadMissing(['period', 'employee', 'payment.proofs.verifier']);
        $p = $r->payment;
        $status = $p?->status ?? PaymentStatus::Unpaid;
        $payable = ! Money::dec($r->school_deductions_total)->isZero();
        $account = PaymentAccount::current();
        $period = $r->period;

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'total' => Money::str($r->school_deductions_total),
            'has_payable' => $payable,
            'rejection_reason' => $status === PaymentStatus::Rejected ? $p->rejection_reason : null,
            'verified_at' => $p?->verified_at?->toIso8601String(),
            'proofs' => $p ? $p->proofs->map(fn ($x) => [
                'id' => $x->id, 'filename' => $x->original_filename, 'size' => $x->file_size, 'mime' => $x->mime_type,
                'uploaded_at' => $x->uploaded_at?->toIso8601String(), 'status' => $x->verification_status,
                'rejection_reason' => $x->rejection_reason, 'verified_at' => $x->verified_at?->toIso8601String(),
                'verifier' => $x->verifier?->name, 'url' => $proofBase.'/'.$x->id,
            ])->values() : [],
            'account' => $account && $account->isComplete() ? [
                'bank' => $account->bank_name, 'number' => $account->account_number, 'holder' => $account->account_holder,
                'instruction' => $account->payment_instruction,
            ] : null,
            'transfer_note' => 'Potongan gaji milik '.$r->employee->full_name.' bulan '.$period->label.'.',
            'can_upload' => $period->status === PeriodStatus::Published && $payable && in_array($status, [PaymentStatus::Unpaid, PaymentStatus::Rejected], true),
            'closed' => $period->status === PeriodStatus::Closed,
        ];
    }
}
