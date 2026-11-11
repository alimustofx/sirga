<?php

namespace App\Enums;

enum ValidationStatus: string
{
    case Valid = 'valid';
    case Mismatch = 'mismatch'; // selisih dengan file sumber, belum diperiksa
    case Copied = 'copied';     // hasil duplikasi periode, belum diverifikasi
    case Invalid = 'invalid';   // hasil hitung negatif

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Mismatch => 'Selisih sumber',
            self::Copied => 'Perlu verifikasi',
            self::Invalid => 'Tidak valid',
        };
    }
}
