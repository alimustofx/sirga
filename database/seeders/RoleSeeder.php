<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [Role::SUPER_ADMIN, 'Super Admin', 'Mengelola seluruh sistem, pengguna, dan konfigurasi.'],
            [Role::FINANCE, 'Admin Keuangan', 'Mengelola penggajian, potongan sekolah, dan verifikasi pembayaran.'],
            [Role::TEACHER, 'Guru', 'Melihat rincian gaji sendiri dan membayar potongan sekolah.'],
        ] as [$name, $label, $desc]) {
            Role::updateOrCreate(['name' => $name], ['label' => $label, 'description' => $desc]);
        }
    }
}
