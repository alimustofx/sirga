<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Storage;

class SchoolAssetController extends Controller
{
    /** Logo sekolah tampil di halaman login (publik). Aset lain hanya untuk admin. */
    public function logo()
    {
        return $this->stream(SchoolSetting::current()->school_logo);
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
