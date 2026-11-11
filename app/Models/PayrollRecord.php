<?php

namespace App\Models;

use App\Enums\ComponentType;
use App\Enums\ValidationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollRecord extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'official_deductions_total' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'school_deductions_total' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
            'source_gross' => 'decimal:2',
            'source_official_deductions' => 'decimal:2',
            'source_net' => 'decimal:2',
            'validation_status' => ValidationStatus::class,
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRecordItem::class)->orderBy('display_order')->orderBy('id');
    }

    public function earnings(): HasMany
    {
        return $this->items()->where('component_type', ComponentType::Earning->value);
    }

    public function officialDeductions(): HasMany
    {
        return $this->items()->where('component_type', ComponentType::OfficialDeduction->value);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class)->orderBy('display_order')->orderBy('id');
    }

    /** Relasi tanpa urutan, untuk agregat (withCount) agar aman di MySQL ONLY_FULL_GROUP_BY. */
    public function deductionRows(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PayrollRecordRevision::class)->orderByDesc('version');
    }
}
