<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCalculator;
use PHPUnit\Framework\TestCase;

class PayrollCalculatorTest extends TestCase
{
    public function test_matches_source_spreadsheet_example(): void
    {
        // Baris contoh dari file sumber: kotor 7.316.960, potongan resmi 914.660, bersih 6.402.300.
        $earn = ['5866400', '586640', '0', '144840', '389000', '273680', '14079', '42238', '0', '0', '83'];
        $off = ['516243', '68420', '273680', '14079', '42238'];
        $r = (new PayrollCalculator())->calculate($earn, $off, ['15000', '25000']);

        $this->assertSame('7316960.00', $r['gross_salary']);
        $this->assertSame('914660.00', $r['official_deductions_total']);
        $this->assertSame('6402300.00', $r['net_salary']);
        $this->assertSame('40000.00', $r['school_deductions_total']);
        $this->assertSame('6362300.00', $r['take_home_pay']);
        $this->assertFalse($r['negative']);
    }

    public function test_flags_negative_results(): void
    {
        $this->assertTrue((new PayrollCalculator())->calculate(['1000'], ['200'], ['900'])['negative']);
        $this->assertTrue((new PayrollCalculator())->calculate(['100'], ['200'], [])['negative']);
    }
}
