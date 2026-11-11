<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'belum_dibayar';
    case Pending = 'menunggu_verifikasi';
    case Paid = 'lunas';
    case Rejected = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Pending => 'Menunggu Verifikasi',
            self::Paid => 'Lunas',
            self::Rejected => 'Ditolak',
        };
    }
}
