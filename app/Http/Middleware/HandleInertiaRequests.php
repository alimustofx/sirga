<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['role', 'employee']);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'role_label' => $user->role->label,
                    'has_employee' => $user->employee !== null,
                    'must_change_password' => $user->must_change_password,
                ] : null,
            ],
            'school' => fn () => ($s = SchoolSetting::current()) ? [
                'name' => $s->school_name,
                'address' => $s->school_address,
                'has_logo' => filled($s->school_logo),
            ] : null,
            'notifications' => fn () => $user ? [
                'unread' => $user->notifications()->whereNull('read_at')->count(),
                'items' => $user->notifications()->latest('id')->limit(8)->get(['id', 'type', 'title', 'body', 'url', 'read_at', 'created_at'])
                    ->map(fn ($n) => [...$n->only(['id', 'type', 'title', 'body', 'url']), 'read' => $n->read_at !== null, 'created_at' => $n->created_at?->toIso8601String()]),
            ] : null,
            'credentials' => fn () => $request->session()->get('credentials'),
        ];
    }
}
