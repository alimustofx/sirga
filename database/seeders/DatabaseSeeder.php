<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, ComponentSeeder::class, SchoolDeductionTypeSeeder::class]);

        SchoolSetting::current();

        // Akun awal. Atur lewat .env: SIRGA_ADMIN_NIP / SIRGA_ADMIN_PASSWORD / SIRGA_FINANCE_NIP / SIRGA_FINANCE_PASSWORD.
        $this->account('Super Admin', env('SIRGA_ADMIN_NIP', '000000000000000001'), env('SIRGA_ADMIN_PASSWORD'), Role::SUPER_ADMIN);
        $this->account('Admin Keuangan', env('SIRGA_FINANCE_NIP', '000000000000000002'), env('SIRGA_FINANCE_PASSWORD'), Role::FINANCE);
    }

    private function account(string $name, string $nip, ?string $password, string $role): void
    {
        if (User::where('nip', $nip)->exists()) {
            return;
        }
        $plain = $password ?: UserAccountService::generatePassword(12);
        User::create([
            'nip' => $nip, 'name' => $name, 'password' => $plain,
            'role_id' => Role::where('name', $role)->value('id'), 'is_active' => true, 'must_change_password' => true,
        ]);
        $this->command?->warn("Akun {$name} dibuat → NIP: {$nip} | Password awal: {$plain} (wajib diganti saat login pertama)");
    }
}
