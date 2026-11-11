<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }

    public function isComplete(): bool
    {
        return filled($this->bank_name) && filled($this->account_number) && filled($this->account_holder);
    }
}
