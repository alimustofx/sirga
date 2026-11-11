<?php

namespace App\Services;

use App\Enums\ComponentType;
use App\Models\PaymentAccount;
use App\Models\PayrollRecord;
use App\Models\SchoolSetting;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayslipService
{
    public function documentNumber(PayrollRecord $record): string
    {
        $p = $record->period;

        return sprintf('SLIP/%d%02d/%05d', $p->period_year, $p->period_month, $record->id);
    }

    public function make(PayrollRecord $record)
    {
        $record->loadMissing(['period', 'employee', 'items', 'deductions', 'payment']);
        $setting = SchoolSetting::current();

        $show = fn ($i) => ! Money::dec($i->amount)->isZero();
        $data = [
            'record' => $record, 'period' => $record->period, 'employee' => $record->employee,
            'earnings' => $record->items->filter(fn ($i) => $i->component_type === ComponentType::Earning && $show($i)),
            'official' => $record->items->filter(fn ($i) => $i->component_type === ComponentType::OfficialDeduction && $show($i)),
            'deductions' => $record->deductions->filter(fn ($d) => $setting->show_zero_deductions || $show($d)),
            'setting' => $setting, 'account' => PaymentAccount::current(),
            'docNumber' => $this->documentNumber($record), 'printedAt' => now()->timezone(config('sirga.timezone')),
            'logo' => $this->image($setting->school_logo), 'qr' => $this->image($setting->treasurer_signature_qr),
        ];

        return Pdf::loadView('pdf.payslip', $data)->setPaper('a4', 'portrait');
    }

    public function filename(PayrollRecord $record): string
    {
        $p = $record->period;

        return 'Slip-Gaji-'.Str::slug($record->employee->full_name).'-'.$p->period_year.str_pad((string) $p->period_month, 2, '0', STR_PAD_LEFT).'.pdf';
    }

    private function image(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }
        $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
