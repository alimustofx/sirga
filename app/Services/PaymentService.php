<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PeriodStatus;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Support\Audit;
use App\Support\Money;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentService
{
    public const DISK = 'local'; // private storage (tidak dapat diakses publik)

    public function statusFor(PayrollRecord $record): PaymentStatus
    {
        return $record->payment?->status ?? PaymentStatus::Unpaid;
    }

    /** Guru mengirim bukti transfer → status Menunggu Verifikasi. */
    public function submit(PayrollRecord $record, UploadedFile $file, User $by): Payment
    {
        $period = $record->period;
        if ($period->status === PeriodStatus::Closed) {
            throw new DomainException('Periode pembayaran ini telah ditutup. Pengunggahan bukti pembayaran sudah tidak tersedia.');
        }
        if ($period->status !== PeriodStatus::Published) {
            throw new DomainException('Periode belum dipublikasikan.');
        }
        if (Money::dec($record->school_deductions_total)->isZero()) {
            throw new DomainException('Tidak ada potongan sekolah yang perlu dibayarkan pada periode ini.');
        }

        $current = $record->payment;
        if ($current?->status === PaymentStatus::Paid) {
            throw new DomainException('Pembayaran sudah dinyatakan lunas.');
        }
        if ($current?->status === PaymentStatus::Pending) {
            throw new DomainException('Bukti pembayaran sebelumnya masih menunggu verifikasi bendahara.');
        }

        $ext = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf',
            default => throw new DomainException('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.'),
        };
        $path = Storage::disk(self::DISK)->putFileAs(
            'payment-proofs/'.$period->id, $file, Str::random(40).'.'.$ext, // nama acak, tidak memakai nama asli
        );

        return DB::transaction(function () use ($record, $file, $by, $path, $ext) {
            $payment = Payment::updateOrCreate(['payroll_record_id' => $record->id], [
                'total_amount' => $record->school_deductions_total, 'status' => PaymentStatus::Pending,
                'submitted_at' => now(), 'verified_at' => null, 'verified_by' => null, 'rejection_reason' => null,
            ]);
            $payment->proofs()->create([
                'file_path' => $path,
                'original_filename' => mb_substr(preg_replace('/[^\w.\- ]+/u', '_', $file->getClientOriginalName()) ?: 'bukti.'.$ext, 0, 190),
                'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(),
                'uploaded_by' => $by->id, 'uploaded_at' => now(), 'verification_status' => 'pending',
            ]);

            $employee = $record->employee;
            Audit::log('payment.upload', $payment, null, ['periode' => $record->period->label, 'nominal' => Money::str($payment->total_amount)], null, $by);
            Notifier::send($by, 'payment_submitted', 'Bukti transfer berhasil dikirim', 'Menunggu verifikasi bendahara.', '/saya/pembayaran/'.$record->payroll_period_id);
            Notifier::toFinance('payment_pending', "Bukti transfer baru: {$employee->full_name}", $record->period->label, '/admin/pembayaran/'.$payment->id);

            return $payment;
        });
    }

    public function approve(Payment $payment, User $by): void
    {
        $this->assertPending($payment);
        DB::transaction(function () use ($payment, $by) {
            $payment->proofs()->first()?->update(['verification_status' => 'approved', 'verified_by' => $by->id, 'verified_at' => now()]);
            $payment->update(['status' => PaymentStatus::Paid, 'verified_at' => now(), 'verified_by' => $by->id, 'rejection_reason' => null]);
            Audit::log('payment.approve', $payment, ['status' => 'menunggu_verifikasi'], ['status' => 'lunas'], null, $by);
        });
        $this->notifyOwner($payment, 'payment_verified', 'Pembayaran diverifikasi', 'Pembayaran potongan Anda telah dinyatakan lunas.');
    }

    public function reject(Payment $payment, string $reason, User $by): void
    {
        $this->assertPending($payment);
        if (blank(trim($reason))) {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }
        DB::transaction(function () use ($payment, $reason, $by) {
            $payment->proofs()->first()?->update(['verification_status' => 'rejected', 'rejection_reason' => $reason, 'verified_by' => $by->id, 'verified_at' => now()]);
            $payment->update(['status' => PaymentStatus::Rejected, 'verified_at' => now(), 'verified_by' => $by->id, 'rejection_reason' => $reason]);
            Audit::log('payment.reject', $payment, ['status' => 'menunggu_verifikasi'], ['status' => 'ditolak'], $reason, $by);
        });
        $this->notifyOwner($payment, 'payment_rejected', 'Bukti transfer ditolak', $reason);
    }

    /** Nominal tagihan mengikuti perubahan potongan sekolah (koreksi/mass update). */
    public function syncTotal(PayrollRecord $record): void
    {
        $payment = $record->payment;
        if ($payment && Money::str($payment->total_amount) !== Money::str($record->school_deductions_total)) {
            $old = Money::str($payment->total_amount);
            $payment->update(['total_amount' => $record->school_deductions_total]);
            Audit::log('payment.total_changed', $payment, ['total' => $old], ['total' => Money::str($record->school_deductions_total)], 'Potongan sekolah berubah; periksa status pembayaran.');
        }
    }

    private function assertPending(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new DomainException('Hanya pembayaran berstatus Menunggu Verifikasi yang dapat diproses.');
        }
        if (! $payment->proofs()->exists()) {
            throw new DomainException('Bukti transfer wajib tersedia sebelum verifikasi.');
        }
    }

    private function notifyOwner(Payment $payment, string $type, string $title, string $body): void
    {
        $record = $payment->record()->with('employee.user')->first();
        if ($user = $record?->employee?->user) {
            Notifier::send($user, $type, $title, $body, '/saya/pembayaran/'.$record->payroll_period_id);
        }
    }
}
