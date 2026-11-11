<?php

namespace Tests\Unit;

use App\Services\Payroll\SourceExcelParser;
use PHPUnit\Framework\TestCase;

class SourceExcelParserTest extends TestCase
{
    private function rows(): array
    {
        return [
            ['DAFTAR GAJI PERIODE DESEMBER 2026'],
            [],
            ['No', 'NIP', 'Nama', 'Gol / Ruang', 'Rekening', 'Gaji Pokok', 'Tunjangan Keluarga', null, 'Gaji Kotor', 'Potongan', null, null, 'Gaji Bersih', 'NPWP', 'Satuan Kerja'],
            [null, null, null, null, null, null, 'Istri / Suami', 'Anak', null, 'PFK 8% Gaji', 'BPJS Kes.4%', 'Jumlah', null, null, null],
            [1, '19690308 199303 2 006', 'Dra. TEST', 'IV/a', '0047013127', 1000, 100, 50, 1150, 80, 20, 100, 1050, '1', 'SMAN'],
            [null, null, null, null, null, null, null, null, 1150, null, null, 100, 1050, null, null],
        ];
    }

    public function test_reads_merged_two_row_headers(): void
    {
        $r = (new SourceExcelParser())->parseRows($this->rows());

        $this->assertSame(['month' => 12, 'year' => 2026], $r['period']);
        $this->assertSame(['e:gajipokok', 'e:tunjangankeluargaistrisuami', 'e:tunjangankeluargaanak', 'd:pfk8gaji', 'd:bpjskes4'], array_column($r['columns'], 'code'));
        $this->assertCount(1, $r['rows']);
        $this->assertSame('196903081993032006', $r['rows'][0]['nip']);
        $this->assertSame('1150.00', $r['rows'][0]['source']['gross']);
        $this->assertSame(['gross' => '1150.00', 'official' => '100.00', 'net' => '1050.00'], $r['control_totals']);
    }

    public function test_reports_invalid_amounts_per_row(): void
    {
        $rows = $this->rows();
        $rows[4][5] = 'abc';
        $rows[4][6] = -5;
        $types = array_column((new SourceExcelParser())->parseRows($rows)['rows'][0]['errors'], 'type');

        $this->assertContains('invalid_amount', $types);
        $this->assertContains('negative_amount', $types);
    }

    public function test_rejects_unknown_structure(): void
    {
        $this->expectException(\DomainException::class);
        (new SourceExcelParser())->parseRows([['a', 'b'], ['c', 'd']]);
    }
}
