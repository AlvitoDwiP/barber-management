<?php

namespace App\Http\Requests\Concerns;

use App\Support\Money;
use Closure;
use InvalidArgumentException;

trait InteractsWithExactMoneyValidation
{
    protected function positiveMoneyRules(string $label): array
    {
        return [
            'decimal:0,2',
            $this->greaterThanZeroMoneyRule($label),
        ];
    }

    protected function nonNegativeMoneyRules(string $label): array
    {
        return [
            'decimal:0,2',
            $this->nonNegativeMoneyRule($label),
        ];
    }

    protected function greaterThanZeroMoneyRule(string $label): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($label): void {
            $money = $this->parseExactMoney($value);

            if ($money === null) {
                return;
            }

            if ($money->minorUnits() <= 0) {
                $fail($label.' harus lebih besar dari 0.');
            }
        };
    }

    protected function nonNegativeMoneyRule(string $label): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($label): void {
            $money = $this->parseExactMoney($value);

            if ($money === null) {
                return;
            }

            if ($money->minorUnits() < 0) {
                $fail($label.' tidak boleh negatif.');
            }
        };
    }

    protected function percentageExceedsHundred(string|int|null $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return false;
        }

        try {
            return Money::parsePercentageToBasisPoints($this->normalizeExactMoneyComparableValue($value)) > 10000;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function parseExactMoney(mixed $value): ?Money
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $normalized = $this->normalizeExactMoneyComparableValue($value);

        if ($normalized === null) {
            return null;
        }

        try {
            return Money::parse($normalized);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function normalizeExactMoneyComparableValue(string|int $value): string|int|null
    {
        if (is_int($value)) {
            return $value;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
