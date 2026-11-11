<?php

namespace App\Support;

final class Indonesian
{
    private const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public static function month(int $m): string
    {
        return self::MONTHS[$m] ?? (string) $m;
    }

    private const SHORT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public static function short(int $m): string
    {
        return self::SHORT[$m] ?? (string) $m;
    }

    public static function months(): array
    {
        return self::MONTHS;
    }
}
