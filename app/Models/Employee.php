<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'nip', 'full_name', 'academic_title', 'employment_status', 'rank', 'position',
        'unit', 'bank_account_number', 'npwp', 'is_active',
    ];

    protected function casts(): array
    {
        return ['employment_status' => EmploymentStatus::class, 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
