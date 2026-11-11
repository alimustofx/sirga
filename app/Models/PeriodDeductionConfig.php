<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodDeductionConfig extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['default_amount' => 'decimal:2', 'is_special' => 'boolean'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SchoolDeductionType::class, 'school_deduction_type_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
}
