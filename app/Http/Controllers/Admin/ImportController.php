<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollImportService;
use App\Services\TemplateService;
use App\Support\Money;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ImportController extends Controller
{
    public function start(PayrollPeriod $period)
    {
        return Inertia::render('admin/ImportStart', ['period' => PayrollPresenter::period($period), 'canImport' => $period->status->isPreparing()]);
    }

    public function upload(Request $request, PayrollPeriod $period, PayrollImportService $service)
    {
        $data = $request->validate([
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ], ['file.mimes' => 'File harus berformat .xls atau .xlsx.', 'file.max' => 'Ukuran file maksimal 10 MB.']);

        return $this->attempt(
            fn () => $service->prepare($period, EmploymentStatus::from($data['employment_status']), $request->file('file'), $request->user()),
            'File berhasil dibaca. Periksa hasil validasi sebelum konfirmasi.',
            fn ($batch) => redirect('/admin/impor/'.$batch->id),
        );
    }

    public function preview(Request $request, ImportBatch $batch, PayrollImportService $service)
    {
        $batch->load(['period', 'rows.errors', 'errors' => fn ($q) => $q->whereNull('import_row_id')]);
        $rows = $batch->rows;
        $active = $rows->where('status', '!=', 'skipped');

        $sum = fn (string $k) => (string) Money::sum($active->pluck('data.system.'.$k));
        $overwritable = $rows->filter(fn ($r) => $r->status === 'error' && $r->errors->where('is_blocking', true)->every(fn ($e) => $e->error_type === 'already_imported'))->count();

        return Inertia::render('admin/ImportPreview', [
            'batch' => [
                'id' => $batch->id, 'filename' => $batch->original_filename, 'title' => $batch->source_title, 'status' => $batch->employment_status,
                'state' => $batch->import_status, 'total' => $batch->total_rows, 'valid' => $batch->valid_rows, 'invalid' => $batch->invalid_rows,
                'skipped' => $rows->where('status', 'skipped')->count(), 'overwritable' => $overwritable,
                'confirmed_at' => $batch->confirmed_at?->toIso8601String(),
            ],
            'period' => PayrollPresenter::period($batch->period),
            'canConfirm' => $batch->import_status === 'previewed' && $batch->period->status->isPreparing(),
            'notices' => $batch->errors->map(fn ($e) => ['type' => $e->error_type, 'message' => $e->error_message]),
            'totals' => ['gross' => $sum('gross'), 'official' => $sum('official'), 'net' => $sum('net')],
            'rows' => $rows->map(fn ($r) => [
                'id' => $r->id, 'row_number' => $r->row_number, 'nip' => $r->nip, 'name' => $r->name, 'status' => $r->status,
                'gross' => $r->data['system']['gross'], 'official' => $r->data['system']['official'], 'net' => $r->data['system']['net'],
                'source' => $r->data['source'],
                'errors' => $r->errors->map(fn ($e) => ['type' => $e->error_type, 'message' => $e->error_message, 'blocking' => $e->is_blocking, 'resolved' => $e->resolved_at !== null])->values(),
            ])->values(),
            'result' => $request->session()->get('import_result'),
        ]);
    }

    public function confirm(Request $request, ImportBatch $batch, PayrollImportService $service)
    {
        $overwrite = $request->boolean('overwrite');

        return $this->attempt(
            fn () => $service->confirm($batch, $overwrite, $request->user()),
            'Impor selesai. Data penghasilan berhasil disimpan.',
            fn ($result) => redirect('/admin/impor/'.$batch->id)->with('import_result', $result),
        );
    }

    public function skip(ImportRow $row, PayrollImportService $service)
    {
        return $this->attempt(fn () => $service->skipRow($row), 'Baris diabaikan dan tidak akan diimpor.');
    }

    public function restore(ImportRow $row, PayrollImportService $service)
    {
        return $this->attempt(fn () => $service->restoreRow($row), 'Baris dikembalikan ke daftar impor.');
    }

    public function cancel(Request $request, ImportBatch $batch, PayrollImportService $service)
    {
        return $this->attempt(fn () => $service->cancel($batch, $request->user()), 'Impor dibatalkan. Tidak ada data yang disimpan.', fn () => redirect('/admin/periode/'.$batch->payroll_period_id));
    }

    public function template(Request $request, string $status, TemplateService $templates)
    {
        abort_unless(in_array($status, ['PNS', 'PPPK'], true), 404);
        $period = PayrollPeriod::find($request->query('period'));
        $file = $templates->build($status, $period?->label ?? '');

        return response()->download($file, "template-gaji-{$status}.xlsx")->deleteFileAfterSend(true);
    }
}
