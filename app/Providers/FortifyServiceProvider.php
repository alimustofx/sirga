<?php

namespace App\Providers;

use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login'));

        // Login memakai NIP (spasi diabaikan). Akun nonaktif ditolak setelah password terverifikasi.
        Fortify::authenticateUsing(function (Request $request) {
            $nip = UserAccountService::normalizeNip((string) $request->input('nip'));
            $user = User::where('nip', $nip)->first();

            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }
            if (! $user->is_active) {
                throw ValidationException::withMessages(['nip' => 'Akun Anda dinonaktifkan. Hubungi Super Admin.']);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower(UserAccountService::normalizeNip((string) $request->input('nip'))).'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });
    }
}
