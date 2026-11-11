<?php

namespace App\Services\Payroll;

use App\Enums\ComponentType;
use App\Enums\PeriodStatus;
use App\Enums\ValidationStatus;
use App\Models\EarningComponent;
use App\Models\Employee;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordItem;
use App\Models\PayrollRecordRevision;
use App\Models\User;
use App\Services\Notifier;
use App\Services\PaymentService;
use App\Support\Audit;
use App\Support\Money;
use DomainException;
use Illuminate\Support\Facades\DB;

class PayrollRecordService
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly PayrollPeriodService $periods,
        private readonly PaymentService $payments,
    ) {}

    /** Input manual: buat rincian kosong dengan seluruh komponen aktif yang berlaku. */
    public function createFor(PayrollPeriod $period, Employee $employee, User $by): PayrollRecord
    {
        $this->assertEditable($period);
        if ($period->records()->where('employee_id', $employee->id)->exists()) {
            throw new DomainException('ASN ini sudah memiliki rincian pada periode tersebut.');
        }

        return DB::transaction(function () use ($period, $employee, $by) {
            $record = PayrollRecord::create(['payroll_period_id' => $period->id, 'employee_id' => $employee->id]);
            $components = EarningComponent::where('is_active', true)
                ->whereIn('applicable_status', ['all', $employee->employment_status->value])
                ->orderBy('component_type')->orderBy('display_order')->orderBy('id')->get();
            foreach ($components as $i => $c) {
                PayrollRecordItem::create([
                    'payroll_record_id' => $record->id, 'earning_component_id' => $c->id,
                    'component_name_snapshot' => $c->name, 'component_type' => $c->component_type,
                    'amount' => '0.00', 'display_order' => $i + 1,
                ]);
            }
            $this->periods->ensureDeductionRows($period);
            $this->calculator->recalculate($record->refresh());
            Audit::log('record.create', $record, null, ['pegawai' => $employee->full_name, 'periode' => $period->label], null, $by);

            return $record;
        });
    }

    /**
     * @param  array<int, array{id?:int|null, component_id?:int|null, type:string, amount:mixed}>  $items
     * @param  array<int, mixed>  $deductions  [payroll_deduction_id => amount]
     */
    public function update(PayrollRecord $record, array $items, array $deductions, ?string $reason, User $by): PayrollRecord
    {
        $period = $record->period;
        $this->assertEditable($period, true);
        if ($period->status === PeriodStatus::Published && blank($reason)) {
            throw new DomainException('Alasan perubahan wajib diisi untuk koreksi setelah publikasi.');
        }

        return DB::transaction(function () use ($record, $items, $deductions, $reason, $by, $period) {
            $before = $this->snapshot($record);
            $this->saveRevision($record, $before, $reason, $by);
            $beforeTake = (string) $record->take_home_pay;

            $existing = $record->items()->get()->keyBy('id');
            $keep = [];
            foreach ($items as $i => $row) {
                $amount = Money::dec($row['amount'] ?? 0);
                if ($amount->isNegative()) {
                    throw new DomainException('Nominal tidak boleh negatif.');
                }
                $type = ComponentType::from($row['type']);
                if (! empty($row['id']) && $existing->has($row['id'])) {
                    $item = $existing[$row['id']];
                    $item->update(['amount' => Money::str($amount), 'display_order' => $i + 1]);
                } else {
                    $comp = EarningComponent::findOrFail($row['component_id'] ?? 0);
                    $item = PayrollRecordItem::create([
                        'payroll_record_id' => $record->id, 'earning_component_id' => $comp->id,
                        'component_name_snapshot' => $comp->name, 'component_type' => $type,
                        'amount' => Money::str($amount), 'display_order' => $i + 1,
                    ]);
                }
                $keep[] = $item->id;
            }
            $record->items()->whereNotIn('id', $keep)->delete();

            $rows = PayrollDeduction::where('payroll_record_id', $record->id)->get()->keyBy('id');
            foreach ($deductions as $id => $amount) {
                $dec = Money::dec($amount);
                if ($dec->isNegative()) {
                    throw new DomainException('Nominal potongan tidak boleh negatif.');
                }
                $rows->get((int) $id)?->update(['amount' => Money::str($dec)]);
            }

            $record->refresh();
            $record->forceFill(['validation_status' => ValidationStatus::Valid, 'validation_note' => null])->save();
            $this->calculator->recalculate($record);
            if ($record->validation_status === ValidationStatus::Invalid) {
                throw new DomainException('Hasil perhitungan bernilai negatif. Periksa kembali nominal penghasilan dan potongan.');
            }

            $after = $this->snapshot($record->refresh());
            Audit::log('record.update', $record, $after === $before ? null : $before, $after, $reason, $by);
            $this->afterChange($record, $beforeTake, $period);

            return $record;
        });
    }

    /** Admin menyatakan selisih/duplikasi telah diperiksa. */
    public function markVerified(PayrollRecord $record, ?string $note, User $by): void
    {
        $this->assertEditable($record->period, true);
        if ($record->validation_status === ValidationStatus::Invalid) {
            throw new DomainException('Rincian bernilai negatif tidak dapat ditandai valid. Perbaiki nominal terlebih dahulu.');
        }
        $old = $record->validation_status->value;
        $record->update(['validation_status' => ValidationStatus::Valid, 'validation_note' => $note]);
        Audit::log('record.verify', $record, ['status' => $old], ['status' => 'valid'], $note, $by);
    }

    public function verifyAll(PayrollPeriod $period, User $by): int
    {
        $this->assertEditable($period);
        $ids = $period->records()->where('validation_status', ValidationStatus::Copied->value)->pluck('id');
        PayrollRecord::whereIn('id', $ids)->update(['validation_status' => ValidationStatus::Valid->value, 'validation_note' => 'Diverifikasi massal setelah duplikasi periode.']);
        Audit::log('record.verify_all', $period, null, ['jumlah' => $ids->count()], 'Verifikasi massal hasil duplikasi', $by);

        return $ids->count();
    }

    /** Dipanggil setelah nominal berubah: sinkron pembayaran dan beri tahu guru bila sudah terbit. */
    public function afterChange(PayrollRecord $record, string $beforeTake, PayrollPeriod $period): void
    {
        $this->payments->syncTotal($record);
        if ($period->status === PeriodStatus::Published && $beforeTake !== (string) $record->take_home_pay) {
            $user = $record->employee?->user;
            if ($user) {
                Notifier::send($user, 'record_corrected', "Rincian gaji {$period->label} diperbarui", 'Terdapat koreksi pada rincian gaji Anda setelah dipublikasikan.', '/saya/rincian/'.$period->id);
            }
        }
    }

    public function snapshot(PayrollRecord $record): array
    {
        $record->loadMissing(['items', 'deductions']);

        return [
            'penghasilan' => $record->items->filter(fn ($i) => $i->component_type === ComponentType::Earning)->mapWithKeys(fn ($i) => [$i->component_name_snapshot => (string) $i->amount])->all(),
            'potongan_resmi' => $record->items->filter(fn ($i) => $i->component_type === ComponentType::OfficialDeduction)->mapWithKeys(fn ($i) => [$i->component_name_snapshot => (string) $i->amount])->all(),
            'potongan_sekolah' => $record->deductions->mapWithKeys(fn ($d) => [$d->deduction_name_snapshot => (string) $d->amount])->all(),
            'total' => [
                'gaji_kotor' => (string) $record->gross_salary, 'potongan_resmi' => (string) $record->official_deductions_total,
                'gaji_bersih' => (string) $record->net_salary, 'potongan_sekolah' => (string) $record->school_deductions_total,
                'diterimakan' => (string) $record->take_home_pay,
            ],
        ];
    }

    public function saveRevision(PayrollRecord $record, array $snapshot, ?string $reason, User $by): void
    {
        $version = (int) PayrollRecordRevision::where('payroll_record_id', $record->id)->max('version') + 1;
        PayrollRecordRevision::create(['payroll_record_id' => $record->id, 'version' => $version, 'snapshot' => $snapshot, 'reason' => $reason, 'changed_by' => $by->id]);
    }

    public function assertEditable(PayrollPeriod $period, bool $allowPublished = false): void
    {
        if ($period->status === PeriodStatus::Closed) {
            throw new DomainException('Periode sudah ditutup (Closed) dan terkunci dari perubahan.');
        }
        if ($period->status === PeriodStatus::Published && ! $allowPublished) {
            throw new DomainException('Periode sudah dipublikasikan. Gunakan koreksi resmi dengan alasan perubahan.');
        }
    }
}
