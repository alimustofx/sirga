<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Support\Indonesian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => PeriodStatus::class, 'published_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function getLabelAttribute(): string
    {
        return Indonesian::month($this->period_month).' '.$this->period_year;
    }

    public function records(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function deductionConfigs(): HasMany
    {
        return $this->hasMany(PeriodDeductionConfig::class)->orderBy('display_order')->orderBy('id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function scopeNewestFirst($q)
    {
        return $q->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function scopeVisibleToTeachers($q)
    {
        return $q->whereIn('status', [PeriodStatus::Published->value, PeriodStatus::Closed->value]);
    }
}
