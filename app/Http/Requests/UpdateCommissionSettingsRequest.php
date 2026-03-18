<?php

namespace App\Http\Requests;

use App\Models\CommissionSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCommissionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_service_commission_type' => $this->normalizeInput($this->input('default_service_commission_type')),
            'default_service_commission_value' => $this->normalizeInput($this->input('default_service_commission_value')),
            'default_product_commission_type' => $this->normalizeInput($this->input('default_product_commission_type')),
            'default_product_commission_value' => $this->normalizeInput($this->input('default_product_commission_value')),
        ]);
    }

    public function rules(): array
    {
        return [
            'default_service_commission_type' => ['required', 'in:'.CommissionSetting::TYPE_PERCENT],
            'default_service_commission_value' => ['required', 'decimal:0,2', 'min:0'],
            'default_product_commission_type' => ['required', 'in:'.implode(',', CommissionSetting::validTypes())],
            'default_product_commission_value' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_service_commission_type.required' => 'Tipe komisi layanan default wajib dipilih.',
            'default_service_commission_type.in' => 'Tipe komisi layanan default harus berupa Persen (%).',
            'default_service_commission_value.required' => 'Nilai komisi layanan default wajib diisi.',
            'default_service_commission_value.decimal' => 'Nilai komisi layanan default maksimal boleh memiliki 2 angka desimal.',
            'default_service_commission_value.min' => 'Nilai komisi layanan default tidak boleh negatif.',
            'default_product_commission_type.required' => 'Tipe komisi produk default wajib dipilih.',
            'default_product_commission_type.in' => 'Tipe komisi produk default harus berupa Persen (%) atau Rupiah (Rp).',
            'default_product_commission_value.required' => 'Nilai komisi produk default wajib diisi.',
            'default_product_commission_value.decimal' => 'Nilai komisi produk default maksimal boleh memiliki 2 angka desimal.',
            'default_product_commission_value.min' => 'Nilai komisi produk default tidak boleh negatif.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $serviceValue = $this->input('default_service_commission_value');

            if ($serviceValue !== null && (float) $serviceValue > 100) {
                $validator->errors()->add(
                    'default_service_commission_value',
                    'Nilai komisi layanan default persen harus berada di antara 0 sampai 100.'
                );
            }

            $this->validatePercentRange(
                $validator,
                'default_product_commission_type',
                'default_product_commission_value',
                'Nilai komisi produk default persen harus berada di antara 0 sampai 100.'
            );
        });
    }

    private function validatePercentRange(
        Validator $validator,
        string $typeField,
        string $valueField,
        string $message
    ): void {
        if ($this->input($typeField) !== CommissionSetting::TYPE_PERCENT) {
            return;
        }

        $value = $this->input($valueField);

        if ($value !== null && (float) $value > 100) {
            $validator->errors()->add($valueField, $message);
        }
    }

    private function normalizeInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
