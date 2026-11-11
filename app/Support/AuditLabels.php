<?php

namespace App\Support;

final class AuditLabels
{
    private const MAP = [
        'auth.login' => 'Masuk ke sistem', 'auth.logout' => 'Keluar dari sistem', 'auth.failed' => 'Percobaan masuk gagal',
        'user.create' => 'Menambah akun ASN', 'user.update' => 'Memperbarui data ASN', 'user.deactivate' => 'Menonaktifkan akun',
        'user.activate' => 'Mengaktifkan akun', 'user.reset_password' => 'Reset password', 'user.change_password' => 'Mengganti password',
        'user.update_email' => 'Memperbarui email akun', 'user.delete' => 'Menghapus data ASN', 'user.bulk_create' => 'Membuat akun ASN massal',
        'period.create' => 'Membuat periode', 'period.duplicate' => 'Menggandakan periode', 'period.review' => 'Memindahkan periode ke Review',
        'period.draft' => 'Mengembalikan periode ke Draft', 'period.publish' => 'Mempublikasikan periode', 'period.close' => 'Menutup periode',
        'period.delete' => 'Menghapus periode', 'import.upload' => 'Mengunggah file Excel', 'import.confirm' => 'Mengonfirmasi impor',
        'import.cancel' => 'Membatalkan impor', 'record.create' => 'Membuat rincian manual', 'record.update' => 'Mengubah rincian gaji',
        'record.verify' => 'Memverifikasi rincian', 'record.verify_all' => 'Verifikasi rincian massal', 'record.overwrite_import' => 'Rincian ditimpa oleh impor',
        'deduction.update' => 'Mengubah potongan sekolah (individual)', 'deduction.mass_update' => 'Mengubah potongan sekolah (massal)',
        'deduction.copy_previous' => 'Menyalin potongan periode sebelumnya', 'deduction.config_add' => 'Menambah jenis potongan pada periode',
        'deduction.config_remove' => 'Menghapus jenis potongan dari periode', 'deduction_type.create' => 'Menambah master potongan',
        'deduction_type.update' => 'Mengubah master potongan', 'deduction_type.delete' => 'Menghapus master potongan', 'deduction_type.reorder' => 'Mengatur urutan potongan',
        'component.create' => 'Menambah komponen penghasilan', 'component.update' => 'Mengubah komponen penghasilan', 'component.delete' => 'Menghapus komponen penghasilan',
        'payment.upload' => 'Mengunggah bukti transfer', 'payment.approve' => 'Menyetujui pembayaran', 'payment.reject' => 'Menolak pembayaran',
        'payment.total_changed' => 'Nominal tagihan berubah', 'announcement.create' => 'Membuat pengumuman', 'announcement.update' => 'Mengubah pengumuman',
        'announcement.archive' => 'Mengarsipkan pengumuman', 'announcement.delete' => 'Menghapus pengumuman',
        'settings.school' => 'Mengubah identitas sekolah', 'settings.document' => 'Mengubah konfigurasi dokumen', 'settings.account' => 'Mengubah rekening bendahara',
        'report.export' => 'Mengekspor laporan', 'payslip.download' => 'Mengunduh slip gaji',
    ];

    public static function label(string $action): string
    {
        return self::MAP[$action] ?? $action;
    }

    /** Kategori yang boleh dilihat Admin Keuangan (tanpa akun/sistem). */
    public static function financePrefixes(): array
    {
        return ['period.', 'import.', 'record.', 'deduction', 'component.', 'payment.', 'announcement.', 'settings.account', 'report.'];
    }
}
