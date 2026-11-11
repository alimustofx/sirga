<?php

namespace App\Models;

use App\Enums\ComponentType;
use Illuminate\Database\Eloquent\Model;

class EarningComponent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['component_type' => ComponentType::class, 'is_active' => 'boolean'];
    }
}
