<?php

namespace Tests\Unit;

use App\Support\TransferNote;
use PHPUnit\Framework\TestCase;

class TransferNoteTest extends TestCase
{
    public function test_short_clear_format(): void
    {
        $this->assertSame('POT FIVA NOV26', TransferNote::compose('Dra. Fiva Tri Wahyuni, M.Pd.', 11, 2026));
        $this->assertSame('POT BUDI JAN27', TransferNote::compose('Budi Santoso, S.Pd.', 1, 2027));
    }

    public function test_skips_common_prefix_and_truncates_to_limit(): void
    {
        $note = TransferNote::compose('Drs. Muhammad Abdurrahman Al-Faruqi, M.M.', 11, 2026);
        $this->assertSame('POT ABDURRAH NOV26', $note);
        $this->assertSame(TransferNote::MAX, strlen($note));
    }

    public function test_never_exceeds_limit_and_only_safe_characters(): void
    {
        foreach (['Hj. Siti Nurhaliza Kusumawardhani, S.Pd., M.Si.', 'Ni Luh Putu Ayu Sriwahyuni, S.Pd.', 'Yohanes Bambang Sutrisno, S.T.', "D'Angelo-O'Brien", '', '123 !!!', 'Muhammad'] as $n) {
            foreach ([1, 9, 12] as $m) {
                $note = TransferNote::compose($n, $m, 2026);
                $this->assertTrue(strlen($note) <= TransferNote::MAX, "terlalu panjang: {$note}");
                $this->assertSame(1, preg_match('/^[A-Z0-9 ]+$/', $note), "karakter tak aman: {$note}");
            }
        }
    }

    public function test_falls_back_when_name_empty(): void
    {
        $this->assertSame('POT ASN DES26', TransferNote::compose('', 12, 2026));
        $this->assertSame('POT MUHAMMAD MEI26', TransferNote::compose('Muhammad', 5, 2026)); // hanya satu kata → tetap dipakai
    }
}
