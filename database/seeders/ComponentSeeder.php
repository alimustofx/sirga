<?php

namespace Database\Seeders;

use App\Enums\ComponentType;
use App\Models\EarningComponent;
use App\Services\Payroll\SourceFormat;
use Illuminate\Database\Seeder;

/** Komponen baku mengikuti struktur kolom file sumber penggajian. */
class ComponentSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;
        foreach (SourceFormat::EARNINGS as [$group, $sub]) {
            $label = SourceFormat::earningLabel($group, $sub);
            $this->make(ComponentType::Earning, $label, ++$order);
        }
        $order = 0;
        foreach (SourceFormat::DEDUCTIONS as $label) {
            $this->make(ComponentType::OfficialDeduction, $label, ++$order);
        }
    }

    private function make(ComponentType $type, string $label, int $order): void
    {
        EarningComponent::firstOrCreate(
            ['code' => SourceFormat::code($type, $label)],
            ['name' => SourceFormat::displayName($type, $label), 'component_type' => $type, 'applicable_status' => 'all', 'display_order' => $order * 10, 'is_active' => true],
        );
    }
}
