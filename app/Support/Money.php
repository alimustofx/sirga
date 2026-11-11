<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

/**
 * Seluruh perhitungan keuangan memakai BigDecimal (bukan float).
 * Nominal disimpan DECIMAL(15,2).
 */
final class Money
{
    public static function dec(mixed $value): BigDecimal
    {
        if ($value === null || $value === '') {
            return BigDecimal::zero();
        }
        if ($value instanceof BigDecimal) {
            return $value;
        }
        if (is_int($value)) {
            return BigDecimal::of($value);
        }
        if (is_float($value)) {
            return BigDecimal::of(number_format($value, 2, '.', ''));
        }

        $s = trim((string) $value);
        $s = preg_replace('/^Rp\.?\s*/i', '', $s) ?? $s;
        $s = str_replace([' ', "\u{00A0}"], '', $s);

        if ($s === '' || $s === '-') {
            return BigDecimal::zero();
        }
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {      // 1.234.567,89
            $s = str_replace(',', '.', str_replace('.', '', $s));
        } elseif (preg_match('/^-?\d+,\d+$/', $s)) {                   // 1234,5
            $s = str_replace(',', '.', $s);
        } elseif (! preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            throw new InvalidArgumentException("Nilai nominal tidak valid: {$value}");
        }

        $d = BigDecimal::of($s);
        if ($d->getScale() > 2 && ! $d->isEqualTo($d->toScale(2, \Brick\Math\RoundingMode::Down))) {
            throw new InvalidArgumentException("Nominal maksimal 2 angka desimal: {$value}");
        }

        return $d;
    }

    /** @param iterable<mixed> $values */
    public static function sum(iterable $values): BigDecimal
    {
        $total = BigDecimal::zero();
        foreach ($values as $v) {
            $total = $total->plus(self::dec($v));
        }

        return $total;
    }

    /** String DECIMAL(15,2) siap simpan, mis. "5423600.00". */
    public static function str(mixed $value): string
    {
        return (string) self::dec($value)->toScale(2, \Brick\Math\RoundingMode::Down);
    }

    /** Format tampilan Indonesia: Rp5.423.600 (desimal hanya bila ada). */
    public static function rupiah(mixed $value): string
    {
        $d = self::dec($value)->toScale(2, \Brick\Math\RoundingMode::Down);
        $neg = $d->isNegative();
        [$int, $frac] = explode('.', (string) $d->abs());
        $int = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $int);
        $out = 'Rp'.$int.($frac !== '00' ? ','.$frac : '');

        return $neg ? '-'.$out : $out;
    }
}
