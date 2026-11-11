<?php

namespace App\Services\Payroll;

use App\Enums\ComponentType;
use App\Support\Money;
use DomainException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Membaca file Excel sumber penggajian dengan header bertingkat (2 baris, sel gabungan).
 * parseRows() murni (tanpa I/O) sehingga mudah diuji.
 */
class SourceExcelParser
{
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    public function parseFile(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
        } catch (Throwable $e) {
            throw new DomainException('File tidak dapat dibaca. Pastikan formatnya .xls atau .xlsx yang valid.');
        }

        return $this->parseRows($rows);
    }

    /** @param array<int, array<int, mixed>> $rows */
    public function parseRows(array $rows): array
    {
        $rows = array_values($rows);
        $h = $this->findHeaderRow($rows);
        $columns = $this->buildColumns($rows[$h], $rows[$h + 1] ?? []);
        $map = $this->classify($columns);

        $title = $this->firstText($rows, $h);
        $out = [
            'title' => $title,
            'period' => $this->periodFromTitle($title),
            'columns' => $map['components'],
            'rows' => [],
            'control_totals' => null,
        ];

        for ($r = $h + 2, $n = count($rows); $r < $n; $r++) {
            $row = $rows[$r];
            $nipRaw = $this->text($row[$map['nip']] ?? null);
            $name = $this->text($row[$map['name']] ?? null);

            if ($nipRaw === '' && $name === '') {
                $gross = $row[$map['gross']] ?? null;
                if (is_numeric($gross) && (float) $gross != 0.0) {
                    $out['control_totals'] = [
                        'gross' => $this->safeMoney($gross),
                        'official' => $this->safeMoney($row[$map['official_total']] ?? null),
                        'net' => $this->safeMoney($row[$map['net']] ?? null),
                    ];
                }

                continue;
            }

            $out['rows'][] = $this->parseRow($r + 1, $row, $map, $nipRaw, $name);
        }

        return $out;
    }

    private function parseRow(int $rowNumber, array $row, array $map, string $nipRaw, string $name): array
    {
        $errors = [];
        $nip = preg_replace('/\s+/', '', $nipRaw) ?? '';
        if ($nip === '') {
            $errors[] = ['type' => 'nip_empty', 'message' => 'NIP kosong.', 'blocking' => true];
        }

        $items = [];
        foreach ($map['components'] as $col) {
            $raw = $row[$col['index']] ?? null;
            try {
                $amount = Money::dec($raw);
                if ($amount->isNegative()) {
                    $errors[] = ['type' => 'negative_amount', 'message' => "Nilai negatif tidak diizinkan pada kolom {$col['label']}.", 'blocking' => true];
                }
                $items[] = ['type' => $col['type'], 'code' => $col['code'], 'name' => $col['name'], 'label' => $col['label'], 'amount' => Money::str($amount)];
            } catch (Throwable) {
                $errors[] = ['type' => 'invalid_amount', 'message' => "Nominal tidak valid pada kolom {$col['label']}: \"".$this->text($raw).'".', 'blocking' => true];
                $items[] = ['type' => $col['type'], 'code' => $col['code'], 'name' => $col['name'], 'label' => $col['label'], 'amount' => '0.00'];
            }
        }

        return [
            'row_number' => $rowNumber,
            'nip' => $nip,
            'name' => $name,
            'rank' => $this->text($row[$map['rank']] ?? null) ?: null,
            'account' => $this->text($row[$map['account']] ?? null) ?: null,
            'npwp' => $this->text($row[$map['npwp']] ?? null) ?: null,
            'unit' => $this->text($row[$map['unit']] ?? null) ?: null,
            'items' => $items,
            'source' => [
                'gross' => $this->safeMoney($row[$map['gross']] ?? null),
                'official' => $this->safeMoney($row[$map['official_total']] ?? null),
                'net' => $this->safeMoney($row[$map['net']] ?? null),
            ],
            'errors' => $errors,
        ];
    }

    private function findHeaderRow(array $rows): int
    {
        foreach (array_slice($rows, 0, 40, true) as $i => $row) {
            if (SourceFormat::key($this->text($row[0] ?? null)) === 'no' && SourceFormat::key($this->text($row[1] ?? null)) === 'nip') {
                return $i;
            }
        }

        throw new DomainException('Struktur header tidak dikenali. Baris header harus diawali kolom "No" dan "NIP". Gunakan template dari aplikasi.');
    }

    /** Header 2 baris → daftar kolom dengan grup & sub (sel gabungan hanya terisi di kolom pertama). */
    private function buildColumns(array $head, array $sub): array
    {
        $cols = [];
        $current = '';
        $width = max(count($head), count($sub));
        for ($c = 0; $c < $width; $c++) {
            $g = $this->text($head[$c] ?? null);
            $s = $this->text($sub[$c] ?? null);
            if ($g !== '') {
                $current = $g;
            }
            $group = $g !== '' ? $g : ($s !== '' ? $current : '');
            $cols[$c] = ['index' => $c, 'own_group' => $g, 'group' => $group, 'sub' => $s];
        }

        return $cols;
    }

    private function classify(array $cols): array
    {
        $find = static function (callable $fn) use ($cols): ?int {
            foreach ($cols as $c) {
                if ($fn($c)) {
                    return $c['index'];
                }
            }

            return null;
        };
        $label = static fn (array $c): string => SourceFormat::key($c['own_group'] !== '' && $c['sub'] === '' ? $c['own_group'] : $c['group'].$c['sub']);

        $nip = $find(fn ($c) => SourceFormat::key($c['group']) === 'nip');
        $name = $find(fn ($c) => SourceFormat::key($c['group']) === 'nama');
        $pokok = $find(fn ($c) => SourceFormat::key($c['group']) === 'gajipokok');
        $gross = $find(fn ($c) => SourceFormat::key($c['group']) === 'gajikotor');
        $net = $find(fn ($c) => SourceFormat::key($c['group']) === 'gajibersih');
        $offTotal = $find(fn ($c) => SourceFormat::key($c['group']) === 'potongan' && SourceFormat::key($c['sub']) === 'jumlah');

        $missing = [];
        foreach (['NIP' => $nip, 'Nama' => $name, 'Gaji Pokok' => $pokok, 'Gaji Kotor' => $gross, 'Potongan › Jumlah' => $offTotal, 'Gaji Bersih' => $net] as $k => $v) {
            if ($v === null) {
                $missing[] = $k;
            }
        }
        if ($missing) {
            throw new DomainException('Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.');
        }
        if ($gross <= $pokok) {
            throw new DomainException('Urutan kolom tidak sesuai: "Gaji Kotor" harus berada setelah komponen penghasilan.');
        }

        $components = [];
        $order = 0;
        foreach ($cols as $c) {
            $i = $c['index'];
            if ($i >= $pokok && $i < $gross) {
                $lbl = SourceFormat::earningLabel($c['group'], $c['own_group'] !== '' && $c['sub'] === '' ? null : $c['sub']);
                $type = ComponentType::Earning;
            } elseif (SourceFormat::key($c['group']) === 'potongan' && $i !== $offTotal && $c['sub'] !== '') {
                $lbl = $c['sub'];
                $type = ComponentType::OfficialDeduction;
            } else {
                continue;
            }
            $components[] = [
                'index' => $i,
                'type' => $type->value,
                'label' => $lbl,
                'name' => SourceFormat::displayName($type, $lbl),
                'code' => SourceFormat::code($type, $lbl),
                'order' => ++$order,
            ];
        }

        $opt = static fn (string $key) => $find(fn ($c) => SourceFormat::key($c['group']) === $key);

        return [
            'nip' => $nip, 'name' => $name, 'gross' => $gross, 'net' => $net, 'official_total' => $offTotal,
            'rank' => $opt('golruang') ?? $opt('gol') ?? $opt('golongan'),
            'account' => $opt('rekening'), 'npwp' => $opt('npwp'),
            'unit' => $opt('satuankerja'),
            'components' => $components,
        ];
    }

    private function firstText(array $rows, int $h): string
    {
        for ($i = 0; $i < $h; $i++) {
            $t = $this->text($rows[$i][0] ?? null);
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    private function periodFromTitle(string $title): ?array
    {
        if (preg_match('/periode\s+([a-z]+)\s+(\d{4})/i', $title, $m) && isset(self::MONTHS[mb_strtolower($m[1])])) {
            return ['month' => self::MONTHS[mb_strtolower($m[1])], 'year' => (int) $m[2]];
        }

        return null;
    }

    private function text(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_float($v) || is_int($v)) {
            return $v == floor($v) ? number_format($v, 0, '', '') : (string) $v; // NIP/rekening numerik tidak boleh jadi notasi ilmiah
        }

        return trim((string) $v);
    }

    private function safeMoney(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            return Money::str($v);
        } catch (Throwable) {
            return null;
        }
    }
}
