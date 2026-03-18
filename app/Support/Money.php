<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public const ROUND_HALF_UP = 'half_up';
    public const ROUND_DOWN = 'down';
    public const ROUND_UP = 'up';

    private const SCALE = 2;
    private const MINOR_UNIT_MULTIPLIER = 100;
    private const PERCENT_DENOMINATOR = 10000;

    private function __construct(
        private readonly int $minorUnits,
    ) {
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromMinorUnits(int $minorUnits): self
    {
        return new self($minorUnits);
    }

    public static function parse(string|int $amount): self
    {
        return new self(self::parseDecimalToMinorUnits($amount));
    }

    public static function fromInput(string|int|null $amount): self
    {
        if (self::isBlank($amount)) {
            return self::zero();
        }

        /** @var string|int $amount */
        return self::parse($amount);
    }

    public static function nullableFromInput(string|int|null $amount): ?self
    {
        if (self::isBlank($amount)) {
            return null;
        }

        /** @var string|int $amount */
        return self::parse($amount);
    }

    public static function parsePercentageToBasisPoints(string|int $percentage): int
    {
        return self::parse($percentage)->minorUnits();
    }

    public static function percentageFromInput(string|int|null $percentage): int
    {
        return self::fromInput($percentage)->minorUnits();
    }

    public static function applyPercentageToMinorUnits(
        int $amountMinorUnits,
        int $basisPoints,
        string $roundingMode = self::ROUND_HALF_UP
    ): int {
        self::assertValidRoundingMode($roundingMode);

        return self::divideAndRound(
            $amountMinorUnits * $basisPoints,
            self::PERCENT_DENOMINATOR,
            $roundingMode,
        );
    }

    public function add(self $other): self
    {
        return new self($this->minorUnits + $other->minorUnits);
    }

    public function subtract(self $other): self
    {
        return new self($this->minorUnits - $other->minorUnits);
    }

    public function multiplyByInteger(int $multiplier): self
    {
        return new self($this->minorUnits * $multiplier);
    }

    public function percentage(string|int $percentage, string $roundingMode = self::ROUND_HALF_UP): self
    {
        return self::fromMinorUnits(
            self::applyPercentageToMinorUnits(
                $this->minorUnits,
                self::parsePercentageToBasisPoints($percentage),
                $roundingMode,
            )
        );
    }

    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    public function toDecimal(): string
    {
        $isNegative = $this->minorUnits < 0;
        $absoluteAmount = abs($this->minorUnits);
        $wholePart = intdiv($absoluteAmount, self::MINOR_UNIT_MULTIPLIER);
        $fractionPart = str_pad(
            (string) ($absoluteAmount % self::MINOR_UNIT_MULTIPLIER),
            self::SCALE,
            '0',
            STR_PAD_LEFT,
        );

        return ($isNegative ? '-' : '').$wholePart.'.'.$fractionPart;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits;
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }

    private static function parseDecimalToMinorUnits(string|int $amount): int
    {
        if (is_int($amount)) {
            return $amount * self::MINOR_UNIT_MULTIPLIER;
        }

        $normalized = trim($amount);

        if ($normalized === '') {
            throw new InvalidArgumentException('Nilai uang tidak boleh kosong.');
        }

        $isNegative = str_starts_with($normalized, '-');

        if ($isNegative) {
            $normalized = substr($normalized, 1);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException("Nilai uang tidak valid: {$amount}");
        }

        [$wholePart, $fractionPart] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fractionPart = str_pad(substr($fractionPart, 0, self::SCALE), self::SCALE, '0');
        $minorUnits = ((int) $wholePart * self::MINOR_UNIT_MULTIPLIER) + (int) $fractionPart;

        return $isNegative ? -$minorUnits : $minorUnits;
    }

    private static function divideAndRound(int $numerator, int $divisor, string $roundingMode): int
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor harus lebih besar dari 0.');
        }

        $sign = $numerator < 0 ? -1 : 1;
        $absoluteNumerator = abs($numerator);
        $quotient = intdiv($absoluteNumerator, $divisor);
        $remainder = $absoluteNumerator % $divisor;

        if ($remainder === 0) {
            return $sign * $quotient;
        }

        $increment = match ($roundingMode) {
            self::ROUND_DOWN => 0,
            self::ROUND_UP => 1,
            self::ROUND_HALF_UP => ($remainder * 2 >= $divisor) ? 1 : 0,
            default => throw new InvalidArgumentException("Mode pembulatan tidak valid: {$roundingMode}"),
        };

        return $sign * ($quotient + $increment);
    }

    private static function assertValidRoundingMode(string $roundingMode): void
    {
        if (! in_array($roundingMode, [
            self::ROUND_HALF_UP,
            self::ROUND_DOWN,
            self::ROUND_UP,
        ], true)) {
            throw new InvalidArgumentException("Mode pembulatan tidak valid: {$roundingMode}");
        }
    }

    private static function isBlank(string|int|null $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }
}
