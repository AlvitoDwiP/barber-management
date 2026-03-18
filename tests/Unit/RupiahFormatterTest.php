<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../app/helpers.php';

class RupiahFormatterTest extends TestCase
{
    public function test_format_rupiah_defaults_to_whole_rupiah_display_with_exact_rounding(): void
    {
        $this->assertSame('Rp 1.235', format_rupiah('1234.50'));
        $this->assertSame('Rp 1.234', format_rupiah('1234.49'));
    }

    public function test_format_rupiah_can_show_two_decimals_for_exact_decimal_strings(): void
    {
        $this->assertSame('Rp 1.234,50', format_rupiah('1234.50', precision: 2));
        $this->assertSame('Rp 1.234,00', format_rupiah('1234', precision: 2));
    }

    public function test_format_rupiah_can_format_minor_units_explicitly(): void
    {
        $this->assertSame('Rp 1.235', format_rupiah_from_minor_units(123450));
        $this->assertSame('Rp 1.234,50', format_rupiah_from_minor_units(123450, precision: 2));
        $this->assertSame('Rp 1.234,50', format_rupiah('123450', precision: 2, valueIsMinorUnits: true));
    }

    public function test_format_rupiah_preserves_legacy_null_and_integer_inputs(): void
    {
        $this->assertSame('Rp 0', format_rupiah(null));
        $this->assertSame('Rp 125.000', format_rupiah(125000));
    }

    public function test_format_rupiah_rejects_invalid_minor_unit_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        format_rupiah('123.45', valueIsMinorUnits: true);
    }
}
