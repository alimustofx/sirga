<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Roles', [
            'roles' => Role::withCount('users')->orderBy('id')->get(['id', 'name', 'label', 'description'])->map(fn ($r) => $r->only(['id', 'name', 'label', 'description', 'users_count'])),
            'matrix' => [
                ['Melihat rincian gaji sendiri', true, true, true],
                ['Mengunduh slip & mengunggah bukti transfer sendiri', true, true, true],
                ['Mengelola data ASN dan akun', true, false, false],
                ['Mengelola penghasilan, impor Excel, dan periode', true, true, false],
                ['Mengatur potongan sekolah', true, true, false],
                ['Memverifikasi pembayaran', true, true, false],
                ['Pengumuman dan laporan', true, true, false],
                ['Rekening bendahara', true, true, false],
                ['Identitas sekolah & konfigurasi sistem', true, false, false],
                ['Audit aktivitas lengkap', true, false, false],
                ['Audit aktivitas keuangan', true, true, false],
            ],
        ]);
    }
}
