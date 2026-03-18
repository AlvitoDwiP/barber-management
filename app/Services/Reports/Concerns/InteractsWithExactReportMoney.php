<?php

namespace App\Services\Reports\Concerns;

use App\Support\Money;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait InteractsWithExactReportMoney
{
    protected function moneyFromValue(mixed $value): Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        if ($value === null) {
            return Money::zero();
        }

        if (is_float($value)) {
            // Some database drivers hydrate DECIMAL aggregates as float.
            // Normalize immediately to a 2-decimal string and keep all math in minor units after this point.
            $value = number_format($value, 2, '.', '');
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return Money::zero();
            }
        }

        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException('Nilai nominal report harus berupa string decimal exact, integer, atau agregat DECIMAL dari driver database.');
        }

        return Money::fromInput($value);
    }

    protected function moneyToDecimal(mixed $value): string
    {
        return $this->moneyFromValue($value)->toDecimal();
    }

    protected function moneyToMinorUnits(mixed $value): int
    {
        return $this->moneyFromValue($value)->minorUnits();
    }

    protected function moneyFromMinorUnits(int $minorUnits): string
    {
        return Money::fromMinorUnits($minorUnits)->toDecimal();
    }

    protected function sumMoney(Collection $rows, string $field): string
    {
        return $this->moneyFromMinorUnits($this->sumMoneyMinorUnits($rows, $field));
    }

    protected function sumMoneyMinorUnits(Collection $rows, string $field): int
    {
        return $rows->reduce(
            fn (int $carry, mixed $row): int => $carry + $this->moneyToMinorUnits(data_get($row, $field, 0)),
            0,
        );
    }

    protected function divideMinorUnits(
        int $amountMinorUnits,
        int $divisor,
        string $roundingMode = Money::ROUND_HALF_UP
    ): int {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor harus lebih besar dari 0.');
        }

        if ($amountMinorUnits === 0) {
            return 0;
        }

        $sign = $amountMinorUnits < 0 ? -1 : 1;
        $absoluteAmount = abs($amountMinorUnits);
        $quotient = intdiv($absoluteAmount, $divisor);
        $remainder = $absoluteAmount % $divisor;

        if ($remainder === 0) {
            return $sign * $quotient;
        }

        $increment = match ($roundingMode) {
            Money::ROUND_DOWN => 0,
            Money::ROUND_UP => 1,
            Money::ROUND_HALF_UP => ($remainder * 2 >= $divisor) ? 1 : 0,
            default => throw new InvalidArgumentException("Mode pembulatan tidak valid: {$roundingMode}"),
        };

        return $sign * ($quotient + $increment);
    }
}
