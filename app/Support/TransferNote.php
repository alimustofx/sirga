<?php

namespace App\Support;

/**
 * Berita transfer singkat untuk pembayaran potongan sekolah.
 * Format: "POT <NAMA> <BLNTH>"  →  mis. "POT FIVA NOV26".
 * Maksimal 18 karakter (batas terketat berita transfer di sejumlah bank), hanya huruf/angka/spasi.
 */
final class TransferNote
{
    public const MAX = 18;

    private const MONTHS = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];

    /** Gelar & sebutan yang dibuang dari nama. */
    private const TITLES = ['DR', 'DRA', 'DRS', 'IR', 'H', 'HJ', 'PROF', 'KH', 'HAJI', 'HAJJAH', 'S', 'M', 'SPD', 'MPD', 'SKOM', 'SSI', 'ST', 'MM', 'MSI', 'SE', 'SH', 'MH', 'SSOS', 'SPSI', 'SIP', 'SAG', 'SHI', 'SPDI'];

    /** Awalan yang terlalu umum: dilewati bila masih ada nama berikutnya (agar lebih membedakan). */
    private const COMMON = ['MUHAMMAD', 'MOHAMMAD', 'MOHAMAD', 'MUHAMAD', 'MUH', 'MOH', 'MOCH', 'MOHAMMAD', 'NI', 'LUH', 'PUTU', 'GUSTI', 'I'];

    public static function compose(string $fullName, int $month, int $year): string
    {
        $tail = ' '.(self::MONTHS[$month] ?? 'XXX').str_pad((string) ($year % 100), 2, '0', STR_PAD_LEFT);
        $budget = self::MAX - strlen('POT ') - strlen($tail);

        $name = self::firstName($fullName);
        $name = substr($name !== '' ? $name : 'ASN', 0, $budget);

        return 'POT '.$name.$tail;
    }

    /** Nama depan bermakna: tanpa gelar (bagian setelah koma dibuang), tanpa awalan umum, huruf A–Z saja. */
    public static function firstName(string $fullName): string
    {
        $base = explode(',', $fullName)[0] ?? $fullName; // "Fiva, S.Pd., M.Pd." → "Fiva"
        $tokens = [];
        foreach (preg_split('/\s+/', trim($base)) ?: [] as $raw) {
            $t = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $raw));
            if ($t === '' || in_array($t, self::TITLES, true) || strlen($t) < 2 && ! in_array($t, self::COMMON, true)) {
                continue;
            }
            $tokens[] = $t;
        }
        foreach ($tokens as $i => $t) {
            if (in_array($t, self::COMMON, true) && isset($tokens[$i + 1])) {
                continue;
            }

            return $t;
        }

        return $tokens[0] ?? '';
    }
}
