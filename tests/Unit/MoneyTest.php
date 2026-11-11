<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_parses_indonesian_and_plain_formats(): void
    {
        $this->assertSame('5866400.00', Money::str(5866400.0));
        $this->assertSame('1234567.89', Money::str('Rp 1.234.567,89'));
        $this->assertSame('1234.50', Money::str('1234,5'));
        $this->assertSame('0.00', Money::str(null));
        $this->assertSame('0.00', Money::str('-'));
    }

    public function test_rejects_invalid_and_excess_decimals(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::dec('abc');
    }

    public function test_sum_is_exact(): void
    {
        $this->assertSame('0.30', Money::str(Money::sum(['0.10', '0.20']))); // float memberi 0.30000000000000004
    }

    public function test_rupiah_format(): void
    {
        $this->assertSame('Rp5.423.600', Money::rupiah('5423600.00'));
        $this->assertSame('Rp1.234,50', Money::rupiah('1234.50'));
    }
}
