<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolAssetController;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;

// Login/logout disediakan Laravel Fortify (username = NIP).
Route::get('sekolah/logo', [SchoolAssetController::class, 'logo'])->name('school.logo');

Route::middleware(['auth', 'account.ready'])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('ganti-password', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::put('ganti-password', [PasswordChangeController::class, 'update']);

    Route::post('notifikasi/baca-semua', [NotificationController::class, 'readAll']);
    Route::post('notifikasi/{notification}/baca', [NotificationController::class, 'read']);

    Route::get('profil', [ProfileController::class, 'show'])->name('profile');
    Route::put('profil/email', [ProfileController::class, 'updateEmail']);
    Route::put('profil/password', [ProfileController::class, 'updatePassword']);

    // ------------------------------------------------------------------ Guru ("Gaji Saya")
    Route::prefix('saya')->middleware('employee')->group(function () {
        Route::get('/', [Teacher\DashboardController::class, 'index'])->name('teacher.dashboard');
        Route::get('rincian/{period?}', [Teacher\PayrollController::class, 'show'])->name('teacher.payroll');
        Route::get('riwayat', [Teacher\PayrollController::class, 'history'])->name('teacher.history');
        Route::get('slip/{period}', [Teacher\PayrollController::class, 'slip'])->name('teacher.slip');
        Route::get('pembayaran/{period?}', [Teacher\PaymentController::class, 'show'])->name('teacher.payment');
        Route::post('pembayaran/{period}/bukti', [Teacher\PaymentController::class, 'store'])->middleware('throttle:20,1');
        Route::get('bukti/{proof}', [Teacher\PaymentController::class, 'proof']);
        Route::get('pengumuman', [Teacher\AnnouncementController::class, 'index'])->name('teacher.announcements');
        Route::get('pengumuman/{id}', [Teacher\AnnouncementController::class, 'show']);
    });

    // ------------------------------------------------------------------ Admin
    Route::prefix('admin')->middleware('role:super_admin,admin_keuangan')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('asn', [Admin\EmployeeController::class, 'index']);

        Route::get('penghasilan', [Admin\EarningController::class, 'index']);
        Route::post('penghasilan', [Admin\EarningController::class, 'store']);
        Route::get('penghasilan/{record}', [Admin\EarningController::class, 'show']);
        Route::put('penghasilan/{record}', [Admin\EarningController::class, 'update']);
        Route::post('penghasilan/{record}/verifikasi', [Admin\EarningController::class, 'verify']);
        Route::post('periode/{period}/verifikasi-semua', [Admin\EarningController::class, 'verifyAll']);

        Route::get('komponen', [Admin\ComponentController::class, 'index']);
        Route::post('komponen', [Admin\ComponentController::class, 'store']);
        Route::put('komponen/{component}', [Admin\ComponentController::class, 'update']);
        Route::delete('komponen/{component}', [Admin\ComponentController::class, 'destroy']);

        Route::get('potongan', [Admin\DeductionController::class, 'index']);
        Route::post('potongan/jenis', [Admin\DeductionController::class, 'storeType']);
        Route::post('potongan/jenis/urutan', [Admin\DeductionController::class, 'reorder']);
        Route::put('potongan/jenis/{type}', [Admin\DeductionController::class, 'updateType']);
        Route::delete('potongan/jenis/{type}', [Admin\DeductionController::class, 'destroyType']);
        Route::post('potongan/periode/{period}/konfig', [Admin\DeductionController::class, 'addConfig']);
        Route::post('potongan/periode/{period}/salin', [Admin\DeductionController::class, 'copyPrevious']);
        Route::delete('potongan/konfig/{config}', [Admin\DeductionController::class, 'removeConfig']);
        Route::post('potongan/konfig/{config}/massal', [Admin\DeductionController::class, 'mass']);
        Route::put('potongan/rincian/{record}', [Admin\DeductionController::class, 'record']);

        Route::get('periode', [Admin\PeriodController::class, 'index']);
        Route::post('periode', [Admin\PeriodController::class, 'store']);
        Route::get('periode/{period}', [Admin\PeriodController::class, 'show']);
        Route::post('periode/{period}/duplikasi', [Admin\PeriodController::class, 'duplicate']);
        Route::post('periode/{period}/review', [Admin\PeriodController::class, 'review']);
        Route::post('periode/{period}/draft', [Admin\PeriodController::class, 'draft']);
        Route::post('periode/{period}/publikasi', [Admin\PeriodController::class, 'publish']);
        Route::post('periode/{period}/tutup', [Admin\PeriodController::class, 'close']);
        Route::delete('periode/{period}', [Admin\PeriodController::class, 'destroy']);

        Route::get('periode/{period}/impor', [Admin\ImportController::class, 'start']);
        Route::post('periode/{period}/impor', [Admin\ImportController::class, 'upload'])->middleware('throttle:20,1');
        Route::get('impor/template/{status}', [Admin\ImportController::class, 'template']);
        Route::get('impor/{batch}', [Admin\ImportController::class, 'preview']);
        Route::post('impor/{batch}/konfirmasi', [Admin\ImportController::class, 'confirm']);
        Route::post('impor/{batch}/batal', [Admin\ImportController::class, 'cancel']);
        Route::post('impor/baris/{row}/abaikan', [Admin\ImportController::class, 'skip']);
        Route::post('impor/baris/{row}/pulihkan', [Admin\ImportController::class, 'restore']);

        Route::get('pembayaran', [Admin\PaymentController::class, 'index']);
        Route::get('pembayaran/{payment}', [Admin\PaymentController::class, 'show']);
        Route::post('pembayaran/{payment}/setujui', [Admin\PaymentController::class, 'approve']);
        Route::post('pembayaran/{payment}/tolak', [Admin\PaymentController::class, 'reject']);
        Route::get('bukti/{proof}', [Admin\PaymentController::class, 'proof']);

        Route::get('pengumuman', [Admin\AnnouncementController::class, 'index']);
        Route::post('pengumuman', [Admin\AnnouncementController::class, 'store']);
        Route::put('pengumuman/{announcement}', [Admin\AnnouncementController::class, 'update']);
        Route::post('pengumuman/{announcement}/arsip', [Admin\AnnouncementController::class, 'archive']);
        Route::delete('pengumuman/{announcement}', [Admin\AnnouncementController::class, 'destroy']);

        Route::get('laporan', [Admin\ReportController::class, 'index']);
        Route::get('laporan/export', [Admin\ReportController::class, 'export']);

        Route::get('audit', [Admin\AuditController::class, 'index']);

        Route::get('pengaturan', [Admin\SettingsController::class, 'show']);
        Route::post('pengaturan/rekening', [Admin\SettingsController::class, 'account']);
        Route::get('aset/{type}', [SchoolAssetController::class, 'asset']);

        // Khusus Super Admin
        Route::middleware('role:super_admin')->group(function () {
            Route::post('asn', [Admin\EmployeeController::class, 'store']);
            Route::post('asn/impor-akun', [Admin\EmployeeController::class, 'bulkImport'])->middleware('throttle:10,1');
            Route::put('asn/{employee}', [Admin\EmployeeController::class, 'update']);
            Route::post('asn/{employee}/status', [Admin\EmployeeController::class, 'toggle']);
            Route::post('asn/{employee}/reset-password', [Admin\EmployeeController::class, 'resetPassword']);
            Route::delete('asn/{employee}', [Admin\EmployeeController::class, 'destroy']);

            Route::get('role', [Admin\RoleController::class, 'index']);
            Route::post('pengaturan/sekolah', [Admin\SettingsController::class, 'school']);
            Route::post('pengaturan/dokumen', [Admin\SettingsController::class, 'document']);
        });
    });
});
