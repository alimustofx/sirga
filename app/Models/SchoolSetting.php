<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['show_zero_deductions' => 'boolean'];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create(['school_name' => 'SMA Negeri 1 Turen']);
    }
}
