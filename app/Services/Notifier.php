<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

final class Notifier
{
    /** @param User|iterable<User> $users */
    public static function send(User|iterable $users, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $list = $users instanceof User ? [$users] : $users;
        $rows = [];
        foreach ($list as $u) {
            $rows[] = ['user_id' => $u->id, 'type' => $type, 'title' => $title, 'body' => $body, 'url' => $url, 'created_at' => now()];
        }
        if ($rows) {
            UserNotification::insert($rows);
        }
    }

    public static function toFinance(string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $users = User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', [Role::SUPER_ADMIN, Role::FINANCE]))->get();
        self::send($users, $type, $title, $body, $url);
    }
}
