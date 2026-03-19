<?php

namespace App\Support\Transactions;

final class TransactionItemPayload
{
    private function __construct()
    {
    }

    public static function normalizeItemizedTransactionPayload(array $payload): array
    {
        $normalizedPayload = $payload;
        unset($normalizedPayload['services'], $normalizedPayload['products']);

        $defaultEmployeeId = self::normalizeScalar($payload['employee_id'] ?? null);
        $items = self::normalizeItems($payload['items'] ?? [], $defaultEmployeeId);

        return [
            ...$normalizedPayload,
            'notes' => self::normalizeOptionalText($payload['notes'] ?? null),
            'items' => array_values($items),
        ];
    }

    public static function normalizeItems(mixed $items, string|int|null $defaultEmployeeId = null): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(function ($row) use ($defaultEmployeeId) {
                if (! is_array($row)) {
                    return null;
                }

                $normalized = [
                    'item_type' => self::normalizeOptionalText($row['item_type'] ?? self::inferItemType($row)),
                    'service_id' => self::normalizeScalar($row['service_id'] ?? null),
                    'product_id' => self::normalizeScalar($row['product_id'] ?? null),
                    'employee_id' => self::normalizeScalar($row['employee_id'] ?? $defaultEmployeeId),
                    'qty' => self::normalizeScalar($row['qty'] ?? null),
                    'commission_type' => self::normalizeOptionalText($row['commission_type'] ?? null),
                    'commission_value' => self::normalizeOptionalText($row['commission_value'] ?? null),
                ];

                if (! self::rowHasAnySignal($normalized)) {
                    return null;
                }

                if ($normalized['item_type'] === 'service' && blank($normalized['qty'])) {
                    $normalized['qty'] = 1;
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeOptionalText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private static function inferItemType(array $row): ?string
    {
        if (filled($row['service_id'] ?? null)) {
            return 'service';
        }

        if (filled($row['product_id'] ?? null)) {
            return 'product';
        }

        return null;
    }

    private static function rowHasAnySignal(array $row): bool
    {
        return collect([
            $row['item_type'] ?? null,
            $row['service_id'] ?? null,
            $row['product_id'] ?? null,
            $row['employee_id'] ?? null,
            $row['qty'] ?? null,
            $row['commission_type'] ?? null,
            $row['commission_value'] ?? null,
        ])->contains(fn ($value) => filled($value));
    }

    private static function normalizeScalar(mixed $value): string|int|null
    {
        if (is_int($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
