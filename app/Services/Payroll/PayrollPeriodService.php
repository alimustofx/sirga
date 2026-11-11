<?php

namespace App\Services\Payroll;

use App\Enums\PeriodStatus;
use App\Enums\ValidationStatus;
use App\Models\Employee;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordItem;
use App\Models\PaymentAccount;
use App\Models\PeriodDeductionConfig;
use App\Models\SchoolDeductionType;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use DomainException;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    public function create(int $month, int $year, ?string $notes, User $by): PayrollPeriod
    {
        return DB::transaction(function () use ($month, $year, $notes, $by) {
            $period = PayrollPeriod::create([
                'period_month' => $month, 'period_year' => $year, 'status' => PeriodStatus::Draft,
                'notes' => $notes, 'created_by' => $by->id, 'updated_by' => $by->id,
            ]);

            // Snapshot jenis potongan aktif → konfigurasi periode (master tidak memengaruhi periode lama).
            $types = SchoolDeductionType::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
            foreach ($types as $i => $t) {
                $period->deductionConfigs()->create([
                    'school_deduction_type_id' => $t->id, 'name' => $t->name, 'description' => $t->description,
                    'default_amount' => $t->is_recurring ? $t->default_amount : '0.00',
                    'display_order' => $t->display_order ?: $i + 1,
                ]);
            }

            Audit::log('period.create', $period, null, ['periode' => $period->label], null, $by);

            return $period;
        });
    }

    /** Menggandakan periode: struktur, potongan, dan nominal disalin sebagai bahan; wajib diverifikasi ulang. Pembayaran tidak disalin. */
    public function duplicate(PayrollPeriod $source, int $month, int $year, User $by): PayrollPeriod
    {
        return DB::transaction(function () use ($source, $month, $year, $by) {
            $period = PayrollPeriod::create([
                'period_month' => $month, 'period_year' => $year, 'status' => PeriodStatus::Draft,
                'notes' => "Digandakan dari {$source->label}", 'created_by' => $by->id, 'updated_by' => $by->id,
            ]);

            $map = [];
            foreach ($source->deductionConfigs as $cfg) {
                $copy = $period->deductionConfigs()->create($cfg->only([
                    'school_deduction_type_id', 'name', 'description', 'default_amount', 'display_order', 'is_special',
                ]));
                $map[$cfg->id] = $copy->id;
            }

            $activeIds = Employee::active()->pluck('id')->all();
            $records = $source->records()->with(['items', 'deductions'])->whereIn('employee_id', $activeIds)->get();
            foreach ($records as $rec) {
                $new = PayrollRecord::create([
                    'payroll_period_id' => $period->id, 'employee_id' => $rec->employee_id,
                    'validation_status' => ValidationStatus::Copied,
                    'validation_note' => "Disalin dari {$source->label}. Periksa nominal sebelum publikasi.",
                ]);
                foreach ($rec->items as $it) {
                    PayrollRecordItem::create([
                        'payroll_record_id' => $new->id, 'earning_component_id' => $it->earning_component_id,
                        'component_name_snapshot' => $it->component_name_snapshot, 'component_type' => $it->component_type,
                        'amount' => $it->amount, 'display_order' => $it->display_order,
                    ]);
                }
                foreach ($rec->deductions as $d) {
                    if (! isset($map[$d->period_deduction_config_id])) {
                        continue;
                    }
                    PayrollDeduction::create([
                        'payroll_record_id' => $new->id, 'school_deduction_type_id' => $d->school_deduction_type_id,
                        'period_deduction_config_id' => $map[$d->period_deduction_config_id],
                        'deduction_name_snapshot' => $d->deduction_name_snapshot, 'amount' => $d->amount,
                        'description' => $d->description, 'display_order' => $d->display_order,
                    ]);
                }
                $this->calculator->recalculate($new);
            }

            Audit::log('period.duplicate', $period, null, ['dari' => $source->label, 'jumlah_rincian' => $records->count()], null, $by);

            return $period;
        });
    }

    /** Pastikan setiap rincian punya baris untuk tiap jenis potongan periode ini. */
    public function ensureDeductionRows(PayrollPeriod $period): void
    {
        $configs = $period->deductionConfigs()->get();
        if ($configs->isEmpty()) {
            return;
        }
        $period->records()->with('deductions')->get()->each(function (PayrollRecord $rec) use ($configs) {
            $have = $rec->deductions->pluck('period_deduction_config_id')->filter()->all();
            $created = false;
            foreach ($configs as $cfg) {
                if (! in_array($cfg->id, $have, true)) {
                    $this->createDeductionRow($rec, $cfg);
                    $created = true;
                }
            }
            if ($created) {
                $this->calculator->recalculate($rec);
            }
        });
    }

    public function createDeductionRow(PayrollRecord $rec, PeriodDeductionConfig $cfg): PayrollDeduction
    {
        return PayrollDeduction::create([
            'payroll_record_id' => $rec->id, 'school_deduction_type_id' => $cfg->school_deduction_type_id,
            'period_deduction_config_id' => $cfg->id, 'deduction_name_snapshot' => $cfg->name,
            'amount' => $cfg->default_amount, 'description' => $cfg->description, 'display_order' => $cfg->display_order,
        ]);
    }

    public function toReview(PayrollPeriod $period, User $by): void
    {
        if ($period->status !== PeriodStatus::Draft) {
            throw new DomainException('Hanya periode Draft yang dapat dipindahkan ke Review.');
        }
        $this->setStatus($period, PeriodStatus::Review, $by, 'period.review');
    }

    public function backToDraft(PayrollPeriod $period, User $by): void
    {
        if ($period->status !== PeriodStatus::Review) {
            throw new DomainException('Hanya periode Review yang dapat dikembalikan ke Draft.');
        }
        $this->setStatus($period, PeriodStatus::Draft, $by, 'period.draft');
    }

    public function publish(PayrollPeriod $period, User $by): void
    {
        if ($period->status !== PeriodStatus::Review) {
            throw new DomainException('Periode harus berstatus Review sebelum dipublikasikan.');
        }
        if (! $this->readiness($period)['ready']) {
            throw new DomainException('Periode belum dapat dipublikasikan. Selesaikan seluruh masalah kelengkapan data terlebih dahulu.');
        }

        DB::transaction(function () use ($period, $by) {
            $period->update(['status' => PeriodStatus::Published, 'published_at' => now(), 'updated_by' => $by->id]);
            Audit::log('period.publish', $period, ['status' => 'review'], ['status' => 'published'], null, $by);
        });

        $userIds = Employee::active()->whereNotNull('user_id')->pluck('user_id');
        Notifier::send(
            User::whereIn('id', $userIds)->where('is_active', true)->get(),
            'period_published', "Rincian gaji {$period->label} telah terbit",
            'Anda dapat melihat rincian dan mengunduh slip gaji.', '/saya/rincian/'.$period->id,
        );
    }

    public function close(PayrollPeriod $period, User $by): void
    {
        if ($period->status !== PeriodStatus::Published) {
            throw new DomainException('Hanya periode Published yang dapat ditutup.');
        }
        DB::transaction(function () use ($period, $by) {
            $period->update(['status' => PeriodStatus::Closed, 'closed_at' => now(), 'updated_by' => $by->id]);
            Audit::log('period.close', $period, ['status' => 'published'], ['status' => 'closed'], null, $by);
        });
    }

    public function destroy(PayrollPeriod $period, User $by): void
    {
        if ($period->status !== PeriodStatus::Draft || $period->records()->exists()) {
            throw new DomainException('Hanya periode Draft tanpa data penghasilan yang dapat dihapus.');
        }
        Audit::log('period.delete', $period, ['periode' => $period->label], null, null, $by);
        $period->delete();
    }

    private function setStatus(PayrollPeriod $period, PeriodStatus $to, User $by, string $action): void
    {
        $from = $period->status;
        $period->update(['status' => $to, 'updated_by' => $by->id]);
        Audit::log($action, $period, ['status' => $from->value], ['status' => $to->value], null, $by);
    }

    /**
     * Pemeriksaan kelengkapan sebelum publikasi (PRD 4.6.2).
     *
     * @return array{ready:bool,issues:array,warnings:array,stats:array}
     */
    public function readiness(PayrollPeriod $period): array
    {
        $issues = [];
        $warnings = [];

        $employees = Employee::active()->orderBy('full_name')->get(['id', 'full_name']);
        $configCount = $period->deductionConfigs()->reorder()->count();
        $records = $period->records()->withCount('deductionRows')->get()->keyBy('employee_id');
        $activeIds = $employees->pluck('id')->flip();

        $missing = $employees->filter(fn ($e) => ! $records->has($e->id));
        if ($missing->isNotEmpty()) {
            $issues[] = $this->issue('missing_records', "{$missing->count()} ASN aktif belum memiliki rincian penghasilan.", $missing->pluck('full_name'));
        }

        $names = Employee::whereIn('id', $records->keys())->pluck('full_name', 'id');
        $labels = [
            ValidationStatus::Mismatch->value => 'rincian memiliki selisih dengan file sumber dan belum diperiksa.',
            ValidationStatus::Copied->value => 'rincian hasil duplikasi periode belum diverifikasi.',
            ValidationStatus::Invalid->value => 'rincian menghasilkan nilai negatif/tidak valid.',
        ];
        foreach ($labels as $status => $text) {
            $bad = $records->filter(fn ($r) => $activeIds->has($r->employee_id) && $r->validation_status->value === $status);
            if ($bad->isNotEmpty()) {
                $issues[] = $this->issue('validation_'.$status, "{$bad->count()} {$text}", $bad->map(fn ($r) => $names[$r->employee_id] ?? '-'));
            }
        }

        $incomplete = $records->filter(fn ($r) => $activeIds->has($r->employee_id) && $r->deduction_rows_count < $configCount);
        if ($incomplete->isNotEmpty()) {
            $issues[] = $this->issue('deductions_incomplete', "{$incomplete->count()} rincian belum memiliki seluruh jenis potongan sekolah periode ini.", $incomplete->map(fn ($r) => $names[$r->employee_id] ?? '-'));
        }

        $pending = $period->importBatches()->where('import_status', 'previewed')->count();
        if ($pending > 0) {
            $issues[] = $this->issue('pending_import', "{$pending} impor Excel masih menunggu konfirmasi atau dibatalkan.", collect());
        }

        $account = PaymentAccount::current();
        if (! $account || ! $account->isComplete()) {
            $issues[] = $this->issue('payment_account', 'Rekening bendahara belum lengkap (Pengaturan › Rekening Bendahara).', collect());
        }

        $setting = SchoolSetting::current();
        if (blank($setting->treasurer_name) || blank($setting->treasurer_nip)) {
            $warnings[] = 'Nama/NIP bendahara belum diisi; bagian pengesahan slip gaji akan kosong.';
        }
        $inactive = $records->filter(fn ($r) => ! $activeIds->has($r->employee_id))->count();
        if ($inactive > 0) {
            $warnings[] = "{$inactive} rincian milik ASN nonaktif. Periksa apakah masih perlu dipublikasikan.";
        }
        if ($configCount === 0) {
            $warnings[] = 'Belum ada jenis potongan sekolah pada periode ini.';
        }

        return [
            'ready' => $issues === [],
            'issues' => $issues,
            'warnings' => $warnings,
            'stats' => ['employees' => $employees->count(), 'complete' => $employees->count() - $missing->count()],
        ];
    }

    private function issue(string $key, string $message, $names): array
    {
        $names = collect($names);

        return ['key' => $key, 'message' => $message, 'items' => $names->take(12)->values()->all(), 'count' => $names->count()];
    }
}
