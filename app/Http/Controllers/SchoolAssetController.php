<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Support\FaviconMaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolAssetController extends Controller
{
    /** Logo sekolah tampil di halaman login (publik). Aset lain hanya untuk admin. */
    public function logo()
    {
        return $this->stream(SchoolSetting::current()->school_logo);
    }

    /** Favicon dari logo sekolah (Pengaturan › Identitas Sekolah). ?s=180 untuk ikon layar utama iOS. */
    public function favicon(Request $request)
    {
        $size = $request->query('s') === '180' ? 180 : 64;
        $fallback = redirect($size === 180 ? '/apple-touch-icon.png' : '/favicon.svg');
        $logo = SchoolSetting::current()->school_logo;
        if (! $logo || ! Storage::disk('local')->exists($logo) || ! FaviconMaker::available()) {
            return $fallback;
        }

        $cache = 'school-assets/fav-'.md5($logo).'-'.$size.'.png'; // nama berubah saat logo diganti
        if (! Storage::disk('local')->exists($cache)) {
            $png = FaviconMaker::make(Storage::disk('local')->get($logo), $size, $size === 180);
            if ($png === null) {
                return $fallback;
            }
            Storage::disk('local')->put($cache, $png);
        }

        return Storage::disk('local')->response($cache, null, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function asset(string $type)
    {
        $s = SchoolSetting::current();
        $path = match ($type) {
            'logo' => $s->school_logo, 'letterhead' => $s->school_letterhead, 'qr' => $s->treasurer_signature_qr, default => null,
        };

        return $this->stream($path);
    }

    private function stream(?string $path)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
