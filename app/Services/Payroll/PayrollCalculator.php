<?php

namespace App\Services\Payroll;

use App\Enums\ComponentType;
use App\Enums\ValidationStatus;
use App\Models\PayrollDeduction;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordItem;
use App\Support\Money;

/**
 * Domain layer perhitungan:
 *   Gaji Bersih    = Gaji Kotor − Potongan Resmi
 *   Yang Diterima  = Gaji Bersih − Potongan Sekolah
 */
class PayrollCalculator
{
    /** @return array{gross_salary:string,official_deductions_total:string,net_salary:string,school_deductions_total:string,take_home_pay:string,negative:bool} */
    public function calculate(iterable $earnings, iterable $officialDeductions, iterable $schoolDeductions): array
    {
        $gross = Money::sum($earnings);
        $official = Money::sum($officialDeductions);
        $school = Money::sum($schoolDeductions);
        $net = $gross->minus($official);
        $take = $net->minus($school);

        return [
            'gross_salary' => Money::str($gross),
            'official_deductions_total' => Money::str($official),
            'net_salary' => Money::str($net),
            'school_deductions_total' => Money::str($school),
            'take_home_pay' => Money::str($take),
            'negative' => $net->isNegative() || $take->isNegative(),
        ];
    }

    public function recalculate(PayrollRecord $record): PayrollRecord
    {
        $items = PayrollRecordItem::where('payroll_record_id', $record->id)->get(['component_type', 'amount']);
        $calc = $this->calculate(
            $items->filter(fn ($i) => $i->component_type === ComponentType::Earning)->pluck('amount'),
            $items->filter(fn ($i) => $i->component_type === ComponentType::OfficialDeduction)->pluck('amount'),
            PayrollDeduction::where('payroll_record_id', $record->id)->pluck('amount'),
        );

        $negative = $calc['negative'];
        unset($calc['negative']);

        $status = $record->validation_status;
        if ($negative) {
            $status = ValidationStatus::Invalid;
        } elseif ($status === ValidationStatus::Invalid) {
            $status = ValidationStatus::Valid;
        }

        $record->forceFill([...$calc, 'validation_status' => $status])->save();

        return $record;
    }
}
