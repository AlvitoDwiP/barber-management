<?php

namespace App\Http\Requests\Concerns;

use Closure;

trait InteractsWithCommissionOverride
{
    abstract protected function allowedCommissionTypes(): array;

    protected function prepareCommissionOverrideForValidation(): void
    {
        $this->merge([
            'commission_type' => $this->normalizeNullableInput($this->input('commission_type')),
            'commission_value' => $this->normalizeNullableInput($this->input('commission_value')),
        ]);
    }

    protected function commissionOverrideRules(): array
    {
        $allowedTypes = $this->allowedCommissionTypes();

        return [
            'commission_type' => ['nullable', 'in:'.implode(',', $allowedTypes), 'required_with:commission_value'],
            'commission_value' => [
                'nullable',
                'decimal:0,2',
                'min:0',
                'required_with:commission_type',
                $this->percentCommissionRangeRule(),
            ],
        ];
    }

    protected function commissionOverrideMessages(): array
    {
        $allowedTypes = $this->allowedCommissionTypes();
        $allowedTypesLabel = implode(' atau ', $allowedTypes);

        return [
            'commission_type.in' => "Tipe komisi harus berupa {$allowedTypesLabel}.",
            'commission_type.required_with' => 'Tipe komisi wajib dipilih saat nilai komisi diisi.',
            'commission_value.required_with' => 'Nilai komisi wajib diisi saat tipe komisi dipilih.',
            'commission_value.decimal' => 'Nilai komisi maksimal boleh memiliki 2 angka desimal.',
            'commission_value.min' => 'Nilai komisi tidak boleh negatif.',
        ];
    }

    protected function percentCommissionRangeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! filled($value) || $this->input('commission_type') !== 'percent') {
                return;
            }

            if ((float) $value > 100) {
                $fail('Nilai komisi persen harus berada di antara 0 sampai 100.');
            }
        };
    }

    private function normalizeNullableInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
