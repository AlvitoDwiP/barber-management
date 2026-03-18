<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_exact_decimal_strings_into_minor_units(): void
    {
        $money = Money::parse('123.45');

        $this->assertSame(12345, $money->minorUnits());
        $this->assertSame('123.45', $money->toDecimal());
    }

    public function test_it_parses_integer_and_string_form_inputs_without_float(): void
    {
        $fromInteger = Money::fromInput(125);
        $fromString = Money::fromInput('125');
        $fromNullableBlank = Money::fromInput('   ');

        $this->assertSame(12500, $fromInteger->minorUnits());
        $this->assertSame('125.00', $fromString->toDecimal());
        $this->assertTrue($fromNullableBlank->isZero());
    }

    public function test_it_converts_minor_units_back_to_exact_decimal_strings(): void
    {
        $positive = Money::fromMinorUnits(12345);
        $negative = Money::fromMinorUnits(-987);

        $this->assertSame('123.45', $positive->toDecimal());
        $this->assertSame('-9.87', $negative->toDecimal());
    }

    public function test_it_supports_exact_integer_based_arithmetic(): void
    {
        $subtotal = Money::parse('100.00')
            ->add(Money::parse('25.50'))
            ->subtract(Money::parse('10.25'));

        $this->assertSame(11525, $subtotal->minorUnits());
        $this->assertSame('115.25', $subtotal->toDecimal());
    }

    public function test_it_applies_percentage_with_explicit_half_up_rounding(): void
    {
        $commission = Money::parse('1000.00')->percentage('33.33');
        $halfCentCase = Money::parse('0.01')->percentage('50.00', Money::ROUND_HALF_UP);

        $this->assertSame('333.30', $commission->toDecimal());
        $this->assertSame(1, $halfCentCase->minorUnits());
    }

    public function test_it_supports_explicit_round_down_and_round_up_for_percentage_results(): void
    {
        $amount = Money::parse('0.01');

        $this->assertSame(
            0,
            $amount->percentage('50.00', Money::ROUND_DOWN)->minorUnits()
        );
        $this->assertSame(
            1,
            $amount->percentage('50.00', Money::ROUND_UP)->minorUnits()
        );
    }

    public function test_it_can_convert_percent_values_to_basis_points(): void
    {
        $this->assertSame(3333, Money::parsePercentageToBasisPoints('33.33'));
        $this->assertSame(0, Money::percentageFromInput(null));
    }

    public function test_it_rejects_invalid_decimal_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::parse('12.345');
    }
}
