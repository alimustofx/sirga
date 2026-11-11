<?php

namespace App\Enums;

enum ComponentType: string
{
    case Earning = 'earning';
    case OfficialDeduction = 'official_deduction';

    public function label(): string
    {
        return $this === self::Earning ? 'Penghasilan' : 'Potongan Resmi';
    }
}
