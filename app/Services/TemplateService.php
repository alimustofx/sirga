<?php

namespace App\Services;

use App\Services\Payroll\SourceFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Template impor dengan header 2 baris yang sama dengan file sumber penggajian. */
class TemplateService
{
    public function build(string $status, string $periodLabel): string
    {
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Sheet1');
        $col = fn (int $i) => Coordinate::stringFromColumnIndex($i + 1);

        $ws->setCellValue('A1', "DAFTAR USULAN PERMINTAAN GAJI INDUK PERIODE ".strtoupper($periodLabel)." ({$status})");
        $ws->setCellValue('A2', 'SMAN 1 TUREN');

        $c = 0;
        foreach (SourceFormat::IDENTITY as $h) {
            $ws->setCellValue($col($c).'4', $h);
            $ws->mergeCells($col($c).'4:'.$col($c).'5');
            $c++;
        }
        foreach (SourceFormat::EARNINGS as [$g, $s]) {
            $ws->setCellValue($col($c).'4', $g);
            if ($s === null) {
                $ws->mergeCells($col($c).'4:'.$col($c).'5');
            } else {
                $ws->setCellValue($col($c).'5', $s);
            }
            $c++;
        }
        // gabungkan sel grup yang berurutan (Tunjangan Keluarga/Jabatan/Umum)
        $start = count(SourceFormat::IDENTITY);
        foreach (['Tunjangan Keluarga' => 3, 'Tunjangan Jabatan' => 3, 'Tunjangan Umum' => 2] as $g => $n) {
            foreach (SourceFormat::EARNINGS as $i => [$gg]) {
                if ($gg === $g) {
                    $ws->mergeCells($col($start + $i).'4:'.$col($start + $i + $n - 1).'4');
                    break;
                }
            }
        }
        $ws->setCellValue($col($c).'4', 'Gaji Kotor');
        $ws->mergeCells($col($c).'4:'.$col($c).'5');
        $c++;
        $dStart = $c;
        $ws->setCellValue($col($c).'4', 'Potongan');
        foreach ([...SourceFormat::DEDUCTIONS, 'Jumlah'] as $d) {
            $ws->setCellValue($col($c).'5', $d);
            $c++;
        }
        $ws->mergeCells($col($dStart).'4:'.$col($c - 1).'4');
        foreach (['Gaji Bersih', 'NPWP', 'Satuan Kerja'] as $h) {
            $ws->setCellValue($col($c).'4', $h);
            $ws->mergeCells($col($c).'4:'.$col($c).'5');
            $c++;
        }

        $range = 'A4:'.$col($c - 1).'5';
        $ws->getStyle($range)->getFont()->setBold(true);
        $ws->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $ws->getStyle('A1')->getFont()->setBold(true);
        for ($i = 0; $i < $c; $i++) {
            $ws->getColumnDimension($col($i))->setWidth($i === 2 ? 34 : ($i === 1 ? 24 : 15));
        }
        $ws->freezePane('D6');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        (new Xlsx($ss))->save($tmp);

        return $tmp;
    }
}
