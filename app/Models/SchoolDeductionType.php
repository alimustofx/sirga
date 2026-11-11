<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolDeductionType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['default_amount' => 'decimal:2', 'is_recurring' => 'boolean', 'is_active' => 'boolean'];
    }
}
