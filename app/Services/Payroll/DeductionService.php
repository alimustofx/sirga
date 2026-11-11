<?php

namespace App\Services\Payroll;

use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PeriodDeductionConfig;
use App\Models\User;
use App\Support\Audit;
use App\Support\Money;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Pengaturan potongan sekolah per periode (massal, individual, salin periode sebelumnya). */
class DeductionService
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly PayrollPeriodService $periods,
        private readonly PayrollRecordService $records,
    ) {}

    public function addConfig(PayrollPeriod $period, string $name, ?string $description, mixed $amount, User $by): PeriodDeductionConfig
    {
        $this->records->assertEditable($period, true);
        $dec = $this->amount($amount);

        return DB::transaction(function () use ($period, $name, $description, $dec, $by) {
            $cfg = $period->deductionConfigs()->create([
                'name' => $name, 'description' => $description, 'default_amount' => Money::str($dec), 'is_special' => true,
                'display_order' => (int) $period->deductionConfigs()->reorder()->max('display_order') + 1,
            ]);
            $this->periods->ensureDeductionRows($period);
            Audit::log('deduction.config_add', $cfg, null, ['nama' => $name, 'nominal' => Money::str($dec)], null, $by);

            return $cfg;
        });
    }

    public function removeConfig(PeriodDeductionConfig $cfg, User $by): void
    {
        $period = $cfg->period;
        if (! $period->status->isPreparing()) {
            throw new DomainException('Jenis potongan hanya dapat dihapus dari periode Draft/Review.');
        }
        DB::transaction(function () use ($cfg, $period, $by) {
            $ids = PayrollDeduction::where('period_deduction_config_id', $cfg->id)->pluck('payroll_record_id');
            PayrollDeduction::where('period_deduction_config_id', $cfg->id)->delete();
            Audit::log('deduction.config_remove', $cfg, ['nama' => $cfg->name], null, null, $by);
            $cfg->delete();
            PayrollRecord::whereIn('id', $ids)->get()->each(fn ($r) => $this->calculator->recalculate($r));
        });
    }

    /** Ubah nominal satu jenis potongan untuk banyak ASN sekaligus. @return int jumlah rincian berubah */
    public function massUpdate(PayrollPeriod $period, PeriodDeductionConfig $cfg, mixed $amount, string $scope, ?string $reason, User $by): int
    {
        $this->records->assertEditable($period, true);
        $this->requireReason($period, $reason);
        $new = $this->amount($amount);

        return DB::transaction(function () use ($period, $cfg, $new, $scope, $reason, $by) {
            $rows = PayrollDeduction::where('period_deduction_config_id', $cfg->id)
                ->whereHas('record', function ($q) use ($period, $scope) {
                    $q->where('payroll_period_id', $period->id);
                    if (in_array($scope, ['PNS', 'PPPK'], true)) {
                        $q->whereHas('employee', fn ($e) => $e->where('employment_status', $scope));
                    }
                })->with('record.employee.user')->get();

            $changes = [];
            foreach ($rows as $row) {
                $old = Money::str($row->amount);
                if (Money::dec($old)->isEqualTo($new)) {
                    continue;
                }
                $beforeTake = (string) $row->record->take_home_pay;
                $row->update(['amount' => Money::str($new)]);
                $this->calculator->recalculate($row->record);
                $this->records->afterChange($row->record->refresh(), $beforeTake, $period);
                $changes[$row->record->employee->full_name] = ['sebelum' => $old, 'sesudah' => Money::str($new)];
            }
            if ($scope === 'all') {
                $cfg->update(['default_amount' => Money::str($new)]);
            }
            if ($changes) {
                Audit::log('deduction.mass_update', $cfg, ['potongan' => $cfg->name, 'rincian' => $changes], ['nominal' => Money::str($new), 'cakupan' => $scope, 'jumlah' => count($changes)], $reason, $by);
            }

            return count($changes);
        });
    }

    /** Ubah nominal potongan satu ASN. @param array<int,mixed> $amounts [payroll_deduction_id => nominal] */
    public function setForRecord(PayrollRecord $record, array $amounts, ?string $reason, User $by): void
    {
        $period = $record->period;
        $this->records->assertEditable($period, true);
        $this->requireReason($period, $reason);

        DB::transaction(function () use ($record, $amounts, $reason, $by, $period) {
            $rows = PayrollDeduction::where('payroll_record_id', $record->id)->get()->keyBy('id');
            $old = $new = [];
            $beforeTake = (string) $record->take_home_pay;
            foreach ($amounts as $id => $amount) {
                $row = $rows->get((int) $id);
                if (! $row) {
                    continue;
                }
                $dec = $this->amount($amount);
                if (Money::dec($row->amount)->isEqualTo($dec)) {
                    continue;
                }
                $old[$row->deduction_name_snapshot] = Money::str($row->amount);
                $new[$row->deduction_name_snapshot] = Money::str($dec);
                $row->update(['amount' => Money::str($dec)]);
            }
            if (! $new) {
                return;
            }
            $this->calculator->recalculate($record);
            $this->records->afterChange($record->refresh(), $beforeTake, $period);
            Audit::log('deduction.update', $record, $old, $new, $reason, $by);
        });
    }

    /** Salin konfigurasi dan nominal dari periode sebelumnya (hanya Draft/Review). */
    public function copyPrevious(PayrollPeriod $period, User $by): int
    {
        $this->records->assertEditable($period);
        $prev = PayrollPeriod::where(fn ($q) => $q->where('period_year', '<', $period->period_year)
            ->orWhere(fn ($w) => $w->where('period_year', $period->period_year)->where('period_month', '<', $period->period_month)))
            ->newestFirst()->first();
        if (! $prev) {
            throw new DomainException('Tidak ada periode sebelumnya untuk disalin.');
        }

        return DB::transaction(function () use ($period, $prev, $by) {
            $cfgMap = [];
            foreach ($prev->deductionConfigs as $old) {
                $cfg = $period->deductionConfigs()->where('name', $old->name)->first()
                    ?? $period->deductionConfigs()->create($old->only(['school_deduction_type_id', 'name', 'description', 'display_order', 'is_special']) + ['default_amount' => $old->default_amount]);
                $cfg->update(['default_amount' => $old->default_amount]);
                $cfgMap[$old->id] = $cfg->id;
            }
            $this->periods->ensureDeductionRows($period);

            $count = 0;
            $prevRows = PayrollDeduction::whereIn('period_deduction_config_id', array_keys($cfgMap))
                ->whereHas('record', fn ($q) => $q->where('payroll_period_id', $prev->id))->with('record')->get();
            $byEmployee = $prevRows->groupBy(fn ($r) => $r->record->employee_id);
            foreach ($period->records()->get() as $rec) {
                $changed = false;
                foreach ($byEmployee[$rec->employee_id] ?? [] as $pr) {
                    $row = PayrollDeduction::where('payroll_record_id', $rec->id)->where('period_deduction_config_id', $cfgMap[$pr->period_deduction_config_id])->first();
                    if ($row && Money::str($row->amount) !== Money::str($pr->amount)) {
                        $row->update(['amount' => $pr->amount]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $this->calculator->recalculate($rec);
                    $count++;
                }
            }
            Audit::log('deduction.copy_previous', $period, null, ['dari' => $prev->label, 'rincian_berubah' => $count], null, $by);

            return $count;
        });
    }

    private function amount(mixed $v): \Brick\Math\BigDecimal
    {
        try {
            $d = Money::dec($v);
        } catch (\Throwable) {
            throw new DomainException('Nominal potongan tidak valid.');
        }
        if ($d->isNegative()) {
            throw new DomainException('Nominal potongan tidak boleh negatif.');
        }

        return $d;
    }

    private function requireReason(PayrollPeriod $period, ?string $reason): void
    {
        if ($period->status === \App\Enums\PeriodStatus::Published && blank($reason)) {
            throw new DomainException('Alasan perubahan wajib diisi untuk perubahan setelah publikasi.');
        }
    }
}
