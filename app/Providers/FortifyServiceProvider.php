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

            // Selalu jalankan satu pemeriksaan hash (termasuk bila NIP tidak ada) agar waktu respons seragam.
            $hash = $user?->password ?? '$2y$12$T0O1V49LuyH1oDiZ3K4F7u8RHQhcL4X/wEWrxoj/kmIijPeLFPWgq';
            $valid = Hash::check((string) $request->input('password'), $hash);
            if (! $user || ! $valid) {
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
