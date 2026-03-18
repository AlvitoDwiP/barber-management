<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Product;
use App\Models\Service;
use DomainException;

class CommissionRuleResolver
{
    private const SOURCE_DEFAULT = 'default';
    private const SOURCE_OVERRIDE = 'override';
    private const MONEY_SCALE = 2;
    private const MINOR_UNIT_MULTIPLIER = 100;

    public function __construct(
        private readonly CommissionSettingsService $commissionSettingsService,
    ) {
    }

    public function resolveForService(Service $service, int $finalItemMinorUnits): array
    {
        $rule = $this->resolveServiceRule($service);

        return $this->buildSnapshot(
            $rule['commission_source'],
            $rule['commission_type'],
            $rule['commission_value'],
            $this->calculatePercentageMinorUnits(
                $finalItemMinorUnits,
                $this->percentageValueToBasisPoints($rule['commission_value'])
            )
        );
    }

    public function resolveForProduct(Product $product, int $subtotalMinorUnits, int $qty): array
    {
        $rule = $this->resolveProductRule($product);

        $commissionMinorUnits = match ($rule['commission_type']) {
            CommissionSetting::TYPE_PERCENT => $this->calculatePercentageMinorUnits(
                $subtotalMinorUnits,
                $this->percentageValueToBasisPoints($rule['commission_value'])
            ),
            CommissionSetting::TYPE_FIXED => $this->moneyToMinorUnits($rule['commission_value']) * $qty,
            default => throw new DomainException("Tipe komisi produk {$product->name} tidak valid."),
        };

        return $this->buildSnapshot(
            $rule['commission_source'],
            $rule['commission_type'],
            $rule['commission_value'],
            $commissionMinorUnits
        );
    }

    private function resolveServiceRule(Service $service): array
    {
        return $this->resolveRule(
            overrideType: $service->commission_type,
            overrideValue: $service->commission_value,
            defaultRule: $this->commissionSettingsService->getDefaultServiceCommission(),
            allowedTypes: [CommissionSetting::TYPE_PERCENT],
            context: "layanan {$service->name}",
        );
    }

    private function resolveProductRule(Product $product): array
    {
        return $this->resolveRule(
            overrideType: $product->commission_type,
            overrideValue: $product->commission_value,
            defaultRule: $this->commissionSettingsService->getDefaultProductCommission(),
            allowedTypes: [
                CommissionSetting::TYPE_PERCENT,
                CommissionSetting::TYPE_FIXED,
            ],
            context: "produk {$product->name}",
        );
    }

    private function resolveRule(
        ?string $overrideType,
        string|int|null $overrideValue,
        array $defaultRule,
        array $allowedTypes,
        string $context
    ): array {
        $hasOverrideType = $this->hasValue($overrideType);
        $hasOverrideValue = $this->hasValue($overrideValue);

        if ($hasOverrideType xor $hasOverrideValue) {
            throw new DomainException("Konfigurasi komisi {$context} tidak lengkap.");
        }

        $rule = $hasOverrideType
            ? [
                'commission_source' => self::SOURCE_OVERRIDE,
                'commission_type' => $overrideType,
                'commission_value' => $overrideValue,
            ]
            : [
                'commission_source' => self::SOURCE_DEFAULT,
                'commission_type' => $defaultRule['commission_type'] ?? null,
                'commission_value' => $defaultRule['commission_value'] ?? null,
            ];

        if (! in_array($rule['commission_type'], $allowedTypes, true)) {
            throw new DomainException("Tipe komisi {$context} tidak valid.");
        }

        if (! $this->hasValue($rule['commission_value'])) {
            throw new DomainException("Nilai komisi {$context} tidak valid.");
        }

        return [
            'commission_source' => $rule['commission_source'],
            'commission_type' => $rule['commission_type'],
            'commission_value' => $this->formatMoneyValue($rule['commission_value']),
        ];
    }

    private function buildSnapshot(
        string $commissionSource,
        string $commissionType,
        string $commissionValue,
        int $commissionMinorUnits
    ): array {
        return [
            'commission_source' => $commissionSource,
            'commission_type' => $commissionType,
            'commission_value' => $commissionValue,
            'commission_amount' => $this->formatMoneyFromMinorUnits($commissionMinorUnits),
        ];
    }

    private function hasValue(string|int|null $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function percentageValueToBasisPoints(string $value): int
    {
        return $this->moneyToMinorUnits($value);
    }

    private function moneyToMinorUnits(string|int|null $amount): int
    {
        $normalized = trim((string) ($amount ?? '0'));

        if ($normalized === '') {
            return 0;
        }

        $isNegative = str_starts_with($normalized, '-');

        if ($isNegative) {
            $normalized = substr($normalized, 1);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new DomainException("Nilai komisi tidak valid: {$amount}");
        }

        [$wholePart, $fractionPart] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fractionPart = str_pad(substr($fractionPart, 0, self::MONEY_SCALE), self::MONEY_SCALE, '0');
        $minorUnits = ((int) $wholePart * self::MINOR_UNIT_MULTIPLIER) + (int) $fractionPart;

        return $isNegative ? -$minorUnits : $minorUnits;
    }

    private function formatMoneyValue(string|int|null $amount): string
    {
        return $this->formatMoneyFromMinorUnits($this->moneyToMinorUnits($amount));
    }

    private function formatMoneyFromMinorUnits(int $amount): string
    {
        $isNegative = $amount < 0;
        $absoluteAmount = abs($amount);
        $wholePart = intdiv($absoluteAmount, self::MINOR_UNIT_MULTIPLIER);
        $fractionPart = str_pad((string) ($absoluteAmount % self::MINOR_UNIT_MULTIPLIER), self::MONEY_SCALE, '0', STR_PAD_LEFT);

        return ($isNegative ? '-' : '').$wholePart.'.'.$fractionPart;
    }

    private function calculatePercentageMinorUnits(int $amount, int $basisPoints): int
    {
        return intdiv(($amount * $basisPoints) + 5000, 10000);
    }
}
