<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'announcement_recipients');
    }

    /** draft | scheduled | published | archived */
    public function state(): string
    {
        if (! $this->is_active) {
            return 'archived';
        }
        if ($this->published_at === null) {
            return 'draft';
        }

        return $this->published_at->isFuture() ? 'scheduled' : 'published';
    }

    /** Pengumuman yang tampil untuk seorang pegawai. */
    public function scopeVisibleTo($q, Employee $employee)
    {
        return $q->where('is_active', true)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(function ($w) use ($employee) {
                $w->where('target_type', 'all')
                    ->orWhere('target_type', strtolower($employee->employment_status->value))
                    ->orWhere(fn ($i) => $i->where('target_type', 'individual')
                        ->whereHas('recipients', fn ($r) => $r->where('employees.id', $employee->id)));
            });
    }
}
