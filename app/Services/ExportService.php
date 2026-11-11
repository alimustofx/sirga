<?php

namespace App\Services;

use App\Enums\ComponentType;
use App\Enums\PaymentStatus;
use App\Exports\ArrayExport;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;

/** Menyusun data laporan untuk ekspor Excel / ringkasan. Nominal ekspor berupa angka agar bisa dihitung ulang. */
class ExportService
{
    public function records(PayrollPeriod $period, ?string $status = null, ?string $payment = null)
    {
        return PayrollRecord::where('payroll_period_id', $period->id)
            ->with(['employee', 'items', 'deductions', 'payment.verifier'])
            ->whereHas('employee', fn ($q) => $status ? $q->where('employment_status', $status) : $q)
            ->when($payment, function ($q) use ($payment) {
                $payment === PaymentStatus::Unpaid->value
                    ? $q->where(fn ($w) => $w->whereDoesntHave('payment')->orWhereHas('payment', fn ($p) => $p->where('status', $payment)))
                    : $q->whereHas('payment', fn ($p) => $p->where('status', $payment));
            })
            ->get()->sortBy(fn ($r) => $r->employee->full_name)->values();
    }

    public function summary($records): array
    {
        $sum = fn (string $f) => (string) \App\Support\Money::sum($records->pluck($f));

        return [
            'count' => $records->count(), 'gross' => $sum('gross_salary'), 'official' => $sum('official_deductions_total'),
            'net' => $sum('net_salary'), 'school' => $sum('school_deductions_total'), 'take_home' => $sum('take_home_pay'),
            'paid' => $records->filter(fn ($r) => $r->payment?->status === PaymentStatus::Paid)->count(),
            'pending' => $records->filter(fn ($r) => $r->payment?->status === PaymentStatus::Pending)->count(),
        ];
    }

    public function full(PayrollPeriod $period, $records): ArrayExport
    {
        $earn = $off = $ded = [];
        foreach ($records as $r) {
            foreach ($r->items as $i) {
                $i->component_type === ComponentType::Earning ? $earn[$i->component_name_snapshot] = true : $off[$i->component_name_snapshot] = true;
            }
            foreach ($r->deductions as $d) {
                $ded[$d->deduction_name_snapshot] = true;
            }
        }
        $earn = array_keys($earn);
        $off = array_keys($off);
        $ded = array_keys($ded);

        $head = array_merge(['No', 'NIP', 'Nama', 'Status', 'Golongan', 'Rekening'], $earn, ['Gaji Kotor'], array_map(fn ($n) => $n.' (Pot. Resmi)', $off), ['Total Potongan Resmi', 'Gaji Bersih'], $ded, ['Total Potongan Sekolah', 'Yang Diterimakan', 'Status Pembayaran']);
        $money = range(6, count($head) - 2);

        $rows = [];
        foreach ($records as $n => $r) {
            $e = $r->items->where('component_type', ComponentType::Earning)->pluck('amount', 'component_name_snapshot');
            $o = $r->items->where('component_type', ComponentType::OfficialDeduction)->pluck('amount', 'component_name_snapshot');
            $d = $r->deductions->pluck('amount', 'deduction_name_snapshot');
            $rows[] = array_merge(
                [$n + 1, $r->employee->nip, $r->employee->full_name, $r->employee->employment_status->value, $r->employee->rank, $r->employee->bank_account_number],
                array_map(fn ($k) => (float) ($e[$k] ?? 0), $earn), [(float) $r->gross_salary],
                array_map(fn ($k) => (float) ($o[$k] ?? 0), $off), [(float) $r->official_deductions_total, (float) $r->net_salary],
                array_map(fn ($k) => (float) ($d[$k] ?? 0), $ded), [(float) $r->school_deductions_total, (float) $r->take_home_pay, ($r->payment?->status ?? PaymentStatus::Unpaid)->label()],
            );
        }

        return new ArrayExport('Rekap '.$period->label, $head, $rows, $money);
    }

    public function deductions(PayrollPeriod $period, $records): ArrayExport
    {
        $names = $period->deductionConfigs->pluck('name')->all();
        $head = array_merge(['No', 'NIP', 'Nama', 'Status'], $names, ['Total Potongan Sekolah']);
        $rows = [];
        foreach ($records as $n => $r) {
            $d = $r->deductions->pluck('amount', 'deduction_name_snapshot');
            $rows[] = array_merge([$n + 1, $r->employee->nip, $r->employee->full_name, $r->employee->employment_status->value], array_map(fn ($k) => (float) ($d[$k] ?? 0), $names), [(float) $r->school_deductions_total]);
        }

        return new ArrayExport('Potongan '.$period->label, $head, $rows, range(4, count($head) - 1));
    }

    public function payments(PayrollPeriod $period, $records): ArrayExport
    {
        $head = ['No', 'NIP', 'Nama', 'Status', 'Nominal Potongan', 'Status Pembayaran', 'Tanggal Upload', 'Tanggal Verifikasi', 'Pemeriksa', 'Alasan Penolakan'];
        $rows = [];
        foreach ($records as $n => $r) {
            $p = $r->payment;
            $rows[] = [$n + 1, $r->employee->nip, $r->employee->full_name, $r->employee->employment_status->value, (float) $r->school_deductions_total, ($p?->status ?? PaymentStatus::Unpaid)->label(), $p?->submitted_at?->format('d/m/Y H:i'), $p?->verified_at?->format('d/m/Y H:i'), $p?->verifier?->name, $p?->rejection_reason];
        }

        return new ArrayExport('Pembayaran '.$period->label, $head, $rows, [4]);
    }
}
