<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Product;
use App\Models\Service;
use App\Support\Money;
use DomainException;

class CommissionRuleResolver
{
    private const SOURCE_DEFAULT = 'default';
    private const SOURCE_OVERRIDE = 'override';

    public function __construct(
        private readonly CommissionSettingsService $commissionSettingsService,
    ) {
    }

    public function resolveForService(
        Service $service,
        int $finalItemMinorUnits,
        ?array $customOverride = null
    ): array
    {
        $rule = $customOverride !== null
            ? $this->resolveCustomRule(
                $customOverride,
                [
                    CommissionSetting::TYPE_PERCENT,
                    CommissionSetting::TYPE_FIXED,
                ],
                "item layanan {$service->name}",
            )
            : $this->resolveServiceRule($service);

        return $this->buildSnapshotFromRule(
            $rule,
            $finalItemMinorUnits,
            1,
            "layanan {$service->name}",
        );
    }

    public function resolveForProduct(
        Product $product,
        int $subtotalMinorUnits,
        int $qty,
        ?array $customOverride = null
    ): array
    {
        $rule = $customOverride !== null
            ? $this->resolveCustomRule(
                $customOverride,
                [
                    CommissionSetting::TYPE_PERCENT,
                    CommissionSetting::TYPE_FIXED,
                ],
                "item produk {$product->name}",
            )
            : $this->resolveProductRule($product);

        return $this->buildSnapshotFromRule(
            $rule,
            $subtotalMinorUnits,
            $qty,
            "produk {$product->name}",
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

    private function resolveCustomRule(array $customOverride, array $allowedTypes, string $context): array
    {
        return $this->resolveRule(
            overrideType: $customOverride['commission_type'] ?? null,
            overrideValue: $customOverride['commission_value'] ?? null,
            defaultRule: [],
            allowedTypes: $allowedTypes,
            context: $context,
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
            'commission_value' => Money::fromInput($rule['commission_value'])->toDecimal(),
        ];
    }

    private function buildSnapshotFromRule(
        array $rule,
        int $subtotalMinorUnits,
        int $qty,
        string $context
    ): array {
        $commissionMinorUnits = match ($rule['commission_type']) {
            CommissionSetting::TYPE_PERCENT => Money::applyPercentageToMinorUnits(
                $subtotalMinorUnits,
                Money::parsePercentageToBasisPoints($rule['commission_value']),
            ),
            CommissionSetting::TYPE_FIXED => Money::parse($rule['commission_value'])
                ->multiplyByInteger($qty)
                ->minorUnits(),
            default => throw new DomainException("Tipe komisi {$context} tidak valid."),
        };

        return [
            'commission_source' => $rule['commission_source'],
            'commission_type' => $rule['commission_type'],
            'commission_value' => $rule['commission_value'],
            'commission_amount' => Money::fromMinorUnits($commissionMinorUnits)->toDecimal(),
        ];
    }

    private function hasValue(string|int|null $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }
}
