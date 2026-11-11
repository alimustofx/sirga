<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'Review',
            self::Published => 'Published',
            self::Closed => 'Closed',
        };
    }

    /** Data masih disiapkan admin (import & input manual diizinkan). */
    public function isPreparing(): bool
    {
        return in_array($this, [self::Draft, self::Review], true);
    }

    /** Guru boleh melihat data. */
    public function isVisibleToTeacher(): bool
    {
        return in_array($this, [self::Published, self::Closed], true);
    }
}
