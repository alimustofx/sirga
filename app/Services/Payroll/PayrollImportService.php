<?php

namespace App\Services\Payroll;

use App\Enums\ComponentType;
use App\Enums\EmploymentStatus;
use App\Enums\ValidationStatus;
use App\Models\EarningComponent;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordItem;
use App\Models\User;
use App\Support\Audit;
use App\Support\Indonesian;
use App\Support\Money;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Alur: prepare (parse + validasi, simpan sementara) → preview → confirm (baru menulis ke data penghasilan).
 */
class PayrollImportService
{
    public function __construct(
        private readonly SourceExcelParser $parser,
        private readonly PayrollCalculator $calculator,
        private readonly PayrollPeriodService $periods,
        private readonly PayrollRecordService $records,
    ) {}

    public function prepare(PayrollPeriod $period, EmploymentStatus $status, UploadedFile $file, User $by): ImportBatch
    {
        $this->assertPreparing($period);

        $ext = strtolower($file->getClientOriginalExtension()) === 'xls' ? 'xls' : 'xlsx';
        $path = $file->storeAs('imports', Str::random(40).'.'.$ext, 'local');

        try {
            $parsed = $this->parser->parseFile(Storage::disk('local')->path($path));
            if (! $parsed['rows']) {
                throw new DomainException('File tidak berisi data pegawai.');
            }
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e instanceof DomainException ? $e : new DomainException('File tidak dapat diproses: '.$e->getMessage());
        }

        return DB::transaction(function () use ($period, $status, $file, $by, $path, $parsed) {
            $batch = ImportBatch::create([
                'payroll_period_id' => $period->id, 'employment_status' => $status->value,
                'original_filename' => mb_substr($file->getClientOriginalName(), 0, 190), 'stored_path' => $path,
                'source_title' => $parsed['title'] ?: null, 'uploaded_by' => $by->id, 'import_status' => 'previewed',
            ]);

            if ($parsed['period'] && ($parsed['period']['month'] !== $period->period_month || $parsed['period']['year'] !== $period->period_year)) {
                $batch->errors()->create([
                    'row_number' => 0, 'error_type' => 'period_mismatch', 'is_blocking' => false,
                    'error_message' => 'Judul file menyebut periode '.Indonesian::month($parsed['period']['month']).' '.$parsed['period']['year']
                        .', sedangkan periode yang dipilih adalah '.$period->label.'. Pastikan file sudah benar.',
                ]);
            }

            $employees = Employee::whereIn('nip', collect($parsed['rows'])->pluck('nip')->filter()->all())->get()->keyBy('nip');
            $existing = PayrollRecord::where('payroll_period_id', $period->id)->pluck('id', 'employee_id');
            $seen = [];
            $valid = $invalid = 0;

            foreach ($parsed['rows'] as $row) {
                $errors = $row['errors'];
                $emp = $row['nip'] !== '' ? ($employees[$row['nip']] ?? null) : null;

                if ($row['nip'] !== '') {
                    if (! $emp) {
                        $errors[] = ['type' => 'nip_not_found', 'blocking' => true, 'message' => 'NIP tidak terdaftar sebagai pegawai (masuk daftar pengecualian). Tambahkan data ASN terlebih dahulu atau abaikan baris ini. Sistem tidak membuat akun otomatis.'];
                    } else {
                        if ($emp->employment_status !== $status) {
                            $errors[] = ['type' => 'status_mismatch', 'blocking' => true, 'message' => "Status pegawai di sistem ({$emp->employment_status->value}) berbeda dengan jenis impor ({$status->value})."];
                        }
                        if (! $emp->is_active) {
                            $errors[] = ['type' => 'inactive_employee', 'blocking' => true, 'message' => 'Pegawai berstatus nonaktif.'];
                        }
                        if ($existing->has($emp->id)) {
                            $errors[] = ['type' => 'already_imported', 'blocking' => true, 'message' => 'Rincian periode ini sudah ada untuk pegawai tersebut. Aktifkan opsi "timpa" saat konfirmasi, atau abaikan baris ini.'];
                        }
                    }
                    if (isset($seen[$row['nip']])) {
                        $errors[] = ['type' => 'nip_duplicate', 'blocking' => true, 'message' => "NIP duplikat (sudah muncul pada baris {$seen[$row['nip']]})."];
                    } else {
                        $seen[$row['nip']] = $row['row_number'];
                    }
                }

                // Rekonsiliasi: hasil hitung sistem vs nilai di file sumber.
                $earn = collect($row['items'])->where('type', 'earning')->pluck('amount');
                $off = collect($row['items'])->where('type', 'official_deduction')->pluck('amount');
                $sys = $this->calculator->calculate($earn, $off, []);
                $system = ['gross' => $sys['gross_salary'], 'official' => $sys['official_deductions_total'], 'net' => $sys['net_salary']];
                $mismatch = false;
                foreach (['gross' => 'Gaji Kotor', 'official' => 'Potongan', 'net' => 'Gaji Bersih'] as $k => $label) {
                    if ($row['source'][$k] !== null && ! Money::dec($row['source'][$k])->isEqualTo($system[$k])) {
                        $mismatch = true;
                        $errors[] = ['type' => 'calc_mismatch', 'blocking' => false, 'message' => "{$label} pada file ".Money::rupiah($row['source'][$k]).' ≠ hasil hitung sistem '.Money::rupiah($system[$k]).'. Periksa sebelum publikasi.'];
                    }
                }

                $blocking = collect($errors)->contains(fn ($e) => $e['blocking']);
                $blocking ? $invalid++ : $valid++;

                $importRow = $batch->rows()->create([
                    'row_number' => $row['row_number'], 'nip' => $row['nip'] ?: null, 'name' => $row['name'] ?: null,
                    'status' => $blocking ? 'error' : 'valid',
                    'data' => [
                        'employee_id' => $emp?->id, 'rank' => $row['rank'], 'account' => $row['account'], 'npwp' => $row['npwp'],
                        'items' => $row['items'], 'source' => $row['source'], 'system' => $system, 'mismatch' => $mismatch,
                    ],
                ]);
                foreach ($errors as $e) {
                    $importRow->errors()->create([
                        'import_batch_id' => $batch->id, 'row_number' => $row['row_number'], 'nip' => $row['nip'] ?: null,
                        'error_type' => $e['type'], 'error_message' => $e['message'], 'is_blocking' => $e['blocking'],
                    ]);
                }
            }

            $batch->update(['total_rows' => count($parsed['rows']), 'valid_rows' => $valid, 'invalid_rows' => $invalid]);
            Audit::log('import.upload', $batch, null, ['periode' => $period->label, 'status' => $status->value, 'baris' => count($parsed['rows']), 'bermasalah' => $invalid], null, $by);

            return $batch;
        });
    }

    public function skipRow(ImportRow $row): void
    {
        $this->assertBatchOpen($row->batch());
        $row->errors()->update(['resolved_at' => now()]);
        $row->update(['status' => 'skipped']);
        $this->refreshCounts($row->import_batch_id);
    }

    public function restoreRow(ImportRow $row): void
    {
        $this->assertBatchOpen($row->batch());
        $row->errors()->update(['resolved_at' => null]);
        $blocking = $row->errors()->where('is_blocking', true)->exists();
        $row->update(['status' => $blocking ? 'error' : 'valid']);
        $this->refreshCounts($row->import_batch_id);
    }

    public function cancel(ImportBatch $batch, User $by): void
    {
        $this->assertBatchOpen($batch);
        $batch->update(['import_status' => 'cancelled']);
        Storage::disk('local')->delete($batch->stored_path);
        Audit::log('import.cancel', $batch, null, null, null, $by);
    }

    /** Baris yang masih memblokir konfirmasi. */
    public function blockingRows(ImportBatch $batch, bool $overwrite)
    {
        return $batch->rows()->with('errors')->get()->filter(fn (ImportRow $r) => $r->status === 'error' && ! $this->importable($r, $overwrite));
    }

    public function confirm(ImportBatch $batch, bool $overwrite, User $by): array
    {
        $this->assertBatchOpen($batch);
        $period = $batch->period;
        $this->assertPreparing($period);

        $blocked = $this->blockingRows($batch, $overwrite);
        if ($blocked->isNotEmpty()) {
            throw new DomainException("Masih ada {$blocked->count()} baris bermasalah. Perbaiki file sumber atau abaikan baris tersebut sebelum konfirmasi.");
        }

        return DB::transaction(function () use ($batch, $overwrite, $by, $period) {
            $imported = $overwritten = $mismatch = 0;
            foreach ($batch->rows()->with('errors')->get() as $row) {
                if ($row->status === 'skipped' || ! $this->importable($row, $overwrite) || empty($row->data['employee_id'])) {
                    continue;
                }
                $result = $this->importRow($batch, $period, $row, $by);
                $imported++;
                $overwritten += $result['overwritten'] ? 1 : 0;
                $mismatch += $result['mismatch'] ? 1 : 0;
            }

            $this->periods->ensureDeductionRows($period);
            $batch->update(['import_status' => 'imported', 'confirmed_at' => now()]);
            Audit::log('import.confirm', $batch, null, ['periode' => $period->label, 'diimpor' => $imported, 'ditimpa' => $overwritten, 'selisih_sumber' => $mismatch], null, $by);

            return ['imported' => $imported, 'overwritten' => $overwritten, 'mismatch' => $mismatch, 'skipped' => $batch->rows()->reorder()->where('status', 'skipped')->count()];
        });
    }

    private function importRow(ImportBatch $batch, PayrollPeriod $period, ImportRow $row, User $by): array
    {
        $data = $row->data;
        $employee = Employee::findOrFail($data['employee_id']);
        $record = PayrollRecord::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->first();
        $overwritten = $record !== null;

        if ($record) {
            $before = $this->records->snapshot($record);
            $this->records->saveRevision($record, $before, 'Ditimpa oleh impor Excel', $by);
            Audit::log('record.overwrite_import', $record, $before, null, "Impor Excel {$batch->original_filename}", $by);
            $record->items()->delete();
        } else {
            $record = PayrollRecord::create(['payroll_period_id' => $period->id, 'employee_id' => $employee->id]);
        }

        foreach ($data['items'] as $i => $it) {
            $component = EarningComponent::firstOrCreate(['code' => $it['code']], [
                'name' => $it['name'], 'component_type' => $it['type'], 'applicable_status' => 'all',
                'display_order' => ($i + 1) * 10, 'is_active' => true,
            ]);
            PayrollRecordItem::create([
                'payroll_record_id' => $record->id, 'earning_component_id' => $component->id,
                'component_name_snapshot' => $component->name, 'component_type' => $it['type'],
                'amount' => $it['amount'], 'source_value' => $it['amount'], 'display_order' => $i + 1,
            ]);
        }

        $record->forceFill([
            'source_file_id' => $batch->id,
            'source_gross' => $data['source']['gross'], 'source_official_deductions' => $data['source']['official'], 'source_net' => $data['source']['net'],
            'validation_status' => $data['mismatch'] ? ValidationStatus::Mismatch : ValidationStatus::Valid,
            'validation_note' => $data['mismatch'] ? 'Selisih dengan file sumber; periksa lalu tandai diperiksa.' : null,
        ])->save();
        $this->calculator->recalculate($record);

        // Lengkapi data pegawai yang kosong (tidak menimpa data yang sudah ada).
        $employee->fill(array_filter([
            'rank' => $employee->rank ?: ($data['rank'] ?? null),
            'bank_account_number' => $employee->bank_account_number ?: ($data['account'] ?? null),
            'npwp' => $employee->npwp ?: ($data['npwp'] ?? null),
        ]))->save();

        return ['overwritten' => $overwritten, 'mismatch' => (bool) $data['mismatch']];
    }

    private function importable(ImportRow $row, bool $overwrite): bool
    {
        if ($row->status === 'skipped') {
            return false;
        }
        $open = $row->errors->filter(fn ($e) => $e->is_blocking && $e->resolved_at === null);
        if ($open->isEmpty()) {
            return true;
        }

        return $overwrite && $open->every(fn ($e) => $e->error_type === 'already_imported');
    }

    private function refreshCounts(int $batchId): void
    {
        $batch = ImportBatch::findOrFail($batchId);
        $batch->update([
            'valid_rows' => $batch->rows()->reorder()->where('status', 'valid')->count(),
            'invalid_rows' => $batch->rows()->reorder()->where('status', 'error')->count(),
        ]);
    }

    private function assertPreparing(PayrollPeriod $period): void
    {
        if (! $period->status->isPreparing()) {
            throw new DomainException('Impor hanya dapat dilakukan pada periode berstatus Draft atau Review.');
        }
    }

    private function assertBatchOpen(ImportBatch|\Illuminate\Database\Eloquent\Relations\BelongsTo $batch): void
    {
        $batch = $batch instanceof ImportBatch ? $batch : $batch->first();
        if ($batch->import_status !== 'previewed') {
            throw new DomainException('Impor ini sudah selesai atau dibatalkan.');
        }
    }
}
