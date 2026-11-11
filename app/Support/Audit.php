<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class Audit
{
    public static function log(
        string $action,
        ?Model $entity = null,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
        ?User $user = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => ($user ?? Auth::user())?->getKey(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 250) : null,
        ]);
    }
}
