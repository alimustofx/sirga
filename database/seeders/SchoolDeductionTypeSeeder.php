<?php

namespace Database\Seeders;

use App\Models\SchoolDeductionType;
use Illuminate\Database\Seeder;

class SchoolDeductionTypeSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, default, bulanan otomatis?]
        foreach ([
            ['PGRI', 15000, true], ['Dansos', 25000, true], ['Qurban', 375000, false], ['BAZNAS', 25000, true], ['KORPRI', 5000, true],
        ] as $i => [$name, $amount, $recurring]) {
            SchoolDeductionType::firstOrCreate(['name' => $name], [
                'default_amount' => $amount, 'is_recurring' => $recurring, 'is_active' => true, 'display_order' => $i + 1,
            ]);
        }
    }
}
