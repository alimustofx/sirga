<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman & data milik pengguna yang login tidak boleh tersimpan di cache/bfcache
        // (mencegah data gaji terlihat lewat tombol Back setelah logout di perangkat bersama).
        $type = (string) $h->get('Content-Type');
        if ($request->user() && (str_contains($type, 'text/html') || str_contains($type, 'application/json'))) {
            $h->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
