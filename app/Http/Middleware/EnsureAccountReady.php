<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Menolak akun nonaktif dan memaksa penggantian password awal. */
class EnsureAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['nip' => 'Akun Anda dinonaktifkan. Hubungi Super Admin.']);
        }

        if ($user->must_change_password && ! $request->is('ganti-password', 'logout')) {
            return redirect('/ganti-password');
        }

        return $next($request);
    }
}
