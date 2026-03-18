<?php

use App\Support\Money;

if (! function_exists('format_rupiah')) {
    function format_rupiah(string|int|float|null $value, int $precision = 0, bool $valueIsMinorUnits = false): string
    {
        return 'Rp '.format_nominal($value, precision: $precision, valueIsMinorUnits: $valueIsMinorUnits);
    }
}

if (! function_exists('format_rupiah_from_minor_units')) {
    function format_rupiah_from_minor_units(int|string|null $minorUnits, int $precision = 0): string
    {
        return format_rupiah($minorUnits, precision: $precision, valueIsMinorUnits: true);
    }
}

if (! function_exists('format_nominal')) {
    function format_nominal(string|int|float|null $value, int $precision = 0, bool $valueIsMinorUnits = false): string
    {
        if (! in_array($precision, [0, 2], true)) {
            throw new \InvalidArgumentException("Precision nominal tidak didukung: {$precision}");
        }

        $minorUnits = normalize_nominal_to_minor_units($value, $valueIsMinorUnits);

        return format_minor_units_for_display($minorUnits, $precision);
    }
}

if (! function_exists('normalize_nominal_to_minor_units')) {
    function normalize_nominal_to_minor_units(string|int|float|null $value, bool $valueIsMinorUnits = false): int
    {
        if ($value === null) {
            return 0;
        }

        if ($valueIsMinorUnits) {
            return normalize_minor_units_input($value);
        }

        return normalize_decimal_money_input($value);
    }
}

if (! function_exists('normalize_decimal_money_input')) {
    function normalize_decimal_money_input(string|int|float $value): int
    {
        if (is_int($value)) {
            return Money::parse($value)->minorUnits();
        }

        if (is_float($value)) {
            // Legacy compatibility path for callers that still hand over float report values.
            return Money::parse(number_format($value, 2, '.', ''))->minorUnits();
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return 0;
        }

        return Money::parse($normalized)->minorUnits();
    }
}

if (! function_exists('normalize_minor_units_input')) {
    function normalize_minor_units_input(string|int|float $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (fmod($value, 1.0) !== 0.0) {
                throw new \InvalidArgumentException('Minor units harus berupa bilangan bulat.');
            }

            return (int) $value;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return 0;
        }

        if (! preg_match('/^-?\d+$/', $normalized)) {
            throw new \InvalidArgumentException('Minor units harus berupa string integer exact.');
        }

        return (int) $normalized;
    }
}

if (! function_exists('format_minor_units_for_display')) {
    function format_minor_units_for_display(int $minorUnits, int $precision = 0): string
    {
        return match ($precision) {
            0 => format_whole_rupiah_display($minorUnits),
            2 => format_two_decimal_rupiah_display($minorUnits),
            default => throw new \InvalidArgumentException("Precision nominal tidak didukung: {$precision}"),
        };
    }
}

if (! function_exists('format_whole_rupiah_display')) {
    function format_whole_rupiah_display(int $minorUnits): string
    {
        $roundedMajorUnits = round_minor_units_to_whole_rupiah($minorUnits);

        return number_format($roundedMajorUnits, 0, ',', '.');
    }
}

if (! function_exists('format_two_decimal_rupiah_display')) {
    function format_two_decimal_rupiah_display(int $minorUnits): string
    {
        $isNegative = $minorUnits < 0;
        $absoluteAmount = abs($minorUnits);
        $wholePart = intdiv($absoluteAmount, 100);
        $fractionPart = str_pad((string) ($absoluteAmount % 100), 2, '0', STR_PAD_LEFT);

        return ($isNegative ? '-' : '')
            .number_format($wholePart, 0, ',', '.')
            .','
            .$fractionPart;
    }
}

if (! function_exists('round_minor_units_to_whole_rupiah')) {
    function round_minor_units_to_whole_rupiah(int $minorUnits): int
    {
        $isNegative = $minorUnits < 0;
        $absoluteAmount = abs($minorUnits);
        $wholePart = intdiv($absoluteAmount, 100);
        $fractionPart = $absoluteAmount % 100;

        if ($fractionPart >= 50) {
            $wholePart++;
        }

        return $isNegative ? -$wholePart : $wholePart;
    }
}
