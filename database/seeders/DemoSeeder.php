<?php

namespace Database\Seeders;

use App\Models\EarningComponent;
use App\Models\PaymentAccount;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\Payroll\PayrollPeriodService;
use App\Services\Payroll\PayrollRecordService;
use App\Services\UserAccountService;
use Illuminate\Database\Seeder;

/**
 * DATA FIKTIF untuk uji coba: php artisan db:seed --class=DemoSeeder
 * Semua guru demo berpassword "Demo12345" (wajib diganti saat login pertama).
 */
class DemoSeeder extends Seeder
{
    public function run(UserAccountService $accounts, PayrollPeriodService $periods, PayrollRecordService $records): void
    {
        $this->call(DatabaseSeeder::class);
        $admin = User::where('role_id', Role::where('name', Role::SUPER_ADMIN)->value('id'))->firstOrFail();

        PaymentAccount::firstOrCreate(['account_number' => '0000000000'], ['bank_name' => 'Bank Jatim', 'account_holder' => 'Bendahara SMAN 1 Turen (contoh)', 'payment_instruction' => 'Cantumkan berita transfer sesuai petunjuk.', 'is_active' => true]);

        $people = [
            ['198001012005011001', 'Budi Santoso, S.Pd.', 'PNS', 'IV/a', 5500000],
            ['198505052010012002', 'Siti Rahayu, S.Pd., M.Pd.', 'PNS', 'III/d', 4800000],
            ['199003032015011003', 'Agus Prasetyo, S.Kom.', 'PNS', 'III/b', 3900000],
            ['199202022021122004', 'Dewi Lestari, S.Pd.', 'PPPK', 'IX', 3600000],
            ['199505052021121005', 'Rizky Pratama, S.Si.', 'PPPK', 'IX', 3500000],
        ];
        $emps = [];
        foreach ($people as [$nip, $name, $status, $rank, $base]) {
            [$e] = $accounts->createEmployee(['nip' => $nip, 'full_name' => $name, 'employment_status' => $status, 'rank' => $rank, 'role' => Role::TEACHER], 'Demo12345', $admin);
            $emps[$base] = $e;
        }

        $period = $periods->create((int) now()->month, (int) now()->year, 'Periode demo', $admin);
        $by = EarningComponent::pluck('id', 'name');
        foreach ($emps as $base => $e) {
            $rec = $records->createFor($period, $e, $admin);
            $items = $rec->items()->get()->map(function ($i) use ($base) {
                $amount = match ($i->component_name_snapshot) {
                    'Gaji Pokok' => $base,
                    'Tunjangan Keluarga (Istri/Suami)' => $base * 0.1,
                    'Tunjangan Jabatan (Fungsional)' => 327000,
                    'PFK 8% Gaji' => ($i->component_type->value === 'official_deduction') ? $base * 0.08 : 0,
                    default => 0,
                };

                return ['id' => $i->id, 'type' => $i->component_type->value, 'amount' => number_format($amount, 2, '.', '')];
            })->all();
            $records->update($rec, $items, [], null, $admin);
        }
        $periods->toReview($period->refresh(), $admin);
        $this->command?->info('Demo siap: periode '.$period->label.' berstatus Review. Publikasikan lewat menu Periode Gaji.');
    }
}
