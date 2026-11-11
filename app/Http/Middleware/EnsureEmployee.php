<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Area "Gaji Saya" hanya untuk akun yang terhubung ke data pegawai. */
class EnsureEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user()?->employee;
        if (! $employee || ! $employee->is_active) {
            return $request->user()?->isFinance() ? redirect('/admin') : abort(403, 'Akun Anda belum terhubung dengan data pegawai aktif.');
        }

        return $next($request);
    }
}
