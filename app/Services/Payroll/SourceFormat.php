<?php

namespace App\Services\Payroll;

use App\Enums\ComponentType;

/**
 * Struktur kolom file sumber penggajian (PNS/PPPK) — dipakai template, parser, dan seeder.
 * Format: [grup, sub-kolom|null]
 */
final class SourceFormat
{
    public const IDENTITY = ['No', 'NIP', 'Nama', 'Gol / Ruang', 'Rekening'];

    public const EARNINGS = [
        ['Gaji Pokok', null],
        ['Tunjangan Keluarga', 'Istri / Suami'],
        ['Tunjangan Keluarga', 'Anak'],
        ['Tunjangan Keluarga', 'Pangan'],
        ['Tunjangan Jabatan', 'Struktural'],
        ['Tunjangan Jabatan', 'Fungsional'],
        ['Tunjangan Jabatan', 'Khusus'],
        ['Tunjangan Umum', 'Murni'],
        ['Tunjangan Umum', 'Tamb.'],
        ['BPJS Kes. 4%', null],
        ['JKK', null],
        ['JKm', null],
        ['TAPERA 0,5%', null],
        ['PPh', null],
        ['Pemb.', null],
    ];

    public const DEDUCTIONS = [
        'PFK 8% Gaji', 'TAPERA 0,5%', 'TAPERA 2,5%', 'Tj. Pangan', 'Kelebihan',
        'BPJS Kes. 1%', 'BPJS Kes. 4%', 'JKK', 'JKm', 'PPh',
    ];

    /** Nama tampilan baku komponen penghasilan (kunci = key(label sumber)). */
    public const EARNING_NAMES = [
        'tunjangankeluargaistrisuami' => 'Tunjangan Keluarga (Istri/Suami)',
        'tunjangankeluargaanak' => 'Tunjangan Keluarga (Anak)',
        'tunjangankeluargapangan' => 'Tunjangan Keluarga (Pangan)',
        'tunjanganjabatanstruktural' => 'Tunjangan Jabatan (Struktural)',
        'tunjanganjabatanfungsional' => 'Tunjangan Jabatan (Fungsional)',
        'tunjanganjabatankhusus' => 'Tunjangan Jabatan (Khusus)',
        'tunjanganumummurni' => 'Tunjangan Umum (Murni)',
        'tunjanganumumtamb' => 'Tunjangan Umum (Tambahan)',
        'pemb' => 'Pembulatan',
    ];

    /** Kunci pencocokan: huruf kecil, hanya a-z0-9 ("BPJS Kes.4%" == "BPJS Kes. 4%"). */
    public static function key(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($s)) ?? '';
    }

    public static function code(ComponentType $type, string $label): string
    {
        return ($type === ComponentType::Earning ? 'e:' : 'd:').self::key($label);
    }

    public static function earningLabel(string $group, ?string $sub): string
    {
        return ($sub === null || $sub === '') ? $group : trim($group.' '.$sub);
    }

    public static function displayName(ComponentType $type, string $label): string
    {
        $key = self::key($label);
        if ($type === ComponentType::Earning) {
            foreach (self::EARNINGS as [$g, $sub]) {
                if (self::key(self::earningLabel($g, $sub)) === $key) {
                    return self::EARNING_NAMES[$key] ?? self::earningLabel($g, $sub);
                }
            }

            return $label;
        }
        foreach (self::DEDUCTIONS as $d) {
            if (self::key($d) === $key) {
                return $d;
            }
        }

        return $label;
    }
}
