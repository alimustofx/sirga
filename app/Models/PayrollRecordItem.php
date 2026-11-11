<?php

namespace App\Models;

use App\Enums\ComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecordItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'source_value' => 'decimal:2', 'component_type' => ComponentType::class];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(EarningComponent::class, 'earning_component_id');
    }
}
