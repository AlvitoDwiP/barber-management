<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function serviceItem(
        int|string $serviceId,
        int|string $employeeId,
        ?string $commissionType = null,
        int|string|null $commissionValue = null
    ): array {
        return [
            'item_type' => 'service',
            'service_id' => $serviceId,
            'employee_id' => $employeeId,
            'qty' => 1,
            'commission_type' => $commissionType,
            'commission_value' => $commissionValue,
        ];
    }

    protected function productItem(
        int|string $productId,
        int|string $employeeId,
        int|string $qty = 1,
        ?string $commissionType = null,
        int|string|null $commissionValue = null
    ): array {
        return [
            'item_type' => 'product',
            'product_id' => $productId,
            'employee_id' => $employeeId,
            'qty' => $qty,
            'commission_type' => $commissionType,
            'commission_value' => $commissionValue,
        ];
    }

    protected function transactionItems(
        int|string $employeeId,
        array $services = [],
        array $products = []
    ): array {
        $serviceItems = collect($services)
            ->map(function (mixed $service) use ($employeeId): array {
                if (is_array($service)) {
                    return $this->serviceItem(
                        $service['service_id'],
                        $service['employee_id'] ?? $employeeId,
                        $service['commission_type'] ?? null,
                        $service['commission_value'] ?? null,
                    );
                }

                return $this->serviceItem($service, $employeeId);
            })
            ->values()
            ->all();

        $productItems = collect($products)
            ->map(function (mixed $product, int|string $productId) use ($employeeId): ?array {
                if (is_array($product)) {
                    if (! array_key_exists('product_id', $product)) {
                        return null;
                    }

                    return $this->productItem(
                        $product['product_id'],
                        $product['employee_id'] ?? $employeeId,
                        $product['qty'] ?? 1,
                        $product['commission_type'] ?? null,
                        $product['commission_value'] ?? null,
                    );
                }

                if (! is_numeric($productId)) {
                    return null;
                }

                return $this->productItem($productId, $employeeId, $product);
            })
            ->filter()
            ->values()
            ->all();

        return [...$serviceItems, ...$productItems];
    }
}
