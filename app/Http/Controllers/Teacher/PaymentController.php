<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\PayrollPeriod;
use App\Services\PaymentService;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function show(Request $request, ?PayrollPeriod $period = null)
    {
        $periods = PayrollController::visiblePeriods($request);
        $period ??= $periods->first();
        $record = $period ? PayrollController::recordFor($request, $period) : null;

        return Inertia::render('guru/Payment', [
            'periods' => $periods->map(fn ($p) => PayrollPresenter::period($p))->values(),
            'period' => $period ? PayrollPresenter::period($period) : null,
            'payment' => $record ? PayrollPresenter::payment($record) : null,
            'maxKb' => config('sirga.proof_max_kb'),
        ]);
    }

    public function store(Request $request, PayrollPeriod $period, PaymentService $payments)
    {
        $record = PayrollController::recordFor($request, $period);
        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.config('sirga.proof_max_kb')],
        ], [
            'proof.max' => 'Ukuran file maksimal 5 MB.',
            'proof.mimes' => 'Format file harus JPG, PNG, atau PDF.',
        ]);

        return $this->attempt(
            fn () => $payments->submit($record, $request->file('proof'), $request->user()),
            'Bukti pembayaran berhasil dikirim dan menunggu verifikasi.',
        );
    }

    public function proof(Request $request, PaymentProof $proof)
    {
        $record = $proof->payment->record;
        abort_unless($record->employee_id === $request->user()->employee?->id, 404);

        return $this->file($proof);
    }

    public static function fileResponse(PaymentProof $proof)
    {
        abort_unless(Storage::disk(PaymentService::DISK)->exists($proof->file_path), 404);

        return Storage::disk(PaymentService::DISK)->response($proof->file_path, $proof->original_filename, [
            'Content-Type' => $proof->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    private function file(PaymentProof $proof)
    {
        return self::fileResponse($proof);
    }
}
