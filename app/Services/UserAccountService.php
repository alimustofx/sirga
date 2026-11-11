<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class UserAccountService
{
    public static function generatePassword(int $length = 10): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    public static function normalizeNip(string $nip): string
    {
        return preg_replace('/\s+/', '', trim($nip)) ?? '';
    }

    /** @return array{0:Employee,1:string} [pegawai, password awal (plain, tampil sekali)] */
    public function createEmployee(array $data, ?string $password, User $by): array
    {
        $plain = $password ?: self::generatePassword();

        return DB::transaction(function () use ($data, $plain, $by) {
            $role = Role::where('name', $data['role'] ?? Role::TEACHER)->firstOrFail();
            $user = User::create([
                'nip' => $data['nip'], 'name' => $data['full_name'], 'email' => $data['email'] ?? null,
                'password' => $plain, 'role_id' => $role->id, 'is_active' => true, 'must_change_password' => true,
            ]);
            $employee = Employee::create([
                'user_id' => $user->id, 'nip' => $data['nip'], 'full_name' => $data['full_name'],
                'academic_title' => $data['academic_title'] ?? null, 'employment_status' => $data['employment_status'],
                'rank' => $data['rank'] ?? null, 'position' => $data['position'] ?? null, 'unit' => $data['unit'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null, 'npwp' => $data['npwp'] ?? null, 'is_active' => true,
            ]);
            Audit::log('user.create', $employee, null, ['nip' => $employee->nip, 'nama' => $employee->full_name, 'role' => $role->name], null, $by);

            return [$employee, $plain];
        });
    }

    public function updateEmployee(Employee $employee, array $data, User $by): Employee
    {
        return DB::transaction(function () use ($employee, $data, $by) {
            $old = $employee->only(['nip', 'full_name', 'employment_status', 'rank', 'position', 'unit', 'bank_account_number']);
            $employee->update(collect($data)->only(['nip', 'full_name', 'academic_title', 'employment_status', 'rank', 'position', 'unit', 'bank_account_number', 'npwp'])->all());

            if ($user = $employee->user) {
                $payload = ['nip' => $employee->nip, 'name' => $employee->full_name];
                if (array_key_exists('email', $data)) {
                    $payload['email'] = $data['email'] ?: null;
                }
                if (! empty($data['role']) && $user->id !== $by->id) { // tidak boleh mengubah role sendiri
                    $payload['role_id'] = Role::where('name', $data['role'])->value('id') ?? $user->role_id;
                }
                $user->update($payload);
            }
            Audit::log('user.update', $employee, $this->plain($old), $this->plain($employee->only(array_keys($old))), null, $by);

            return $employee;
        });
    }

    public function setActive(Employee $employee, bool $active, User $by): void
    {
        DB::transaction(function () use ($employee, $active, $by) {
            $employee->update(['is_active' => $active]);
            $employee->user?->update(['is_active' => $active]);
            if (! $active && $employee->user) {
                DB::table('sessions')->where('user_id', $employee->user->id)->delete(); // akhiri sesi aktif
            }
            Audit::log($active ? 'user.activate' : 'user.deactivate', $employee, null, ['nama' => $employee->full_name], null, $by);
        });
    }

    public function resetPassword(Employee $employee, ?string $password, User $by): string
    {
        $plain = $password ?: self::generatePassword();
        $user = $employee->user;
        $user->update(['password' => $plain, 'must_change_password' => true]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Audit::log('user.reset_password', $employee, null, ['nama' => $employee->full_name], null, $by);

        return $plain;
    }

    private function plain(array $a): array
    {
        return array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $a);
    }
}
