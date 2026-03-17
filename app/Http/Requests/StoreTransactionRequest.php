<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    private const MINIMUM_ITEM_MESSAGE = 'Transaksi harus berisi minimal 1 item: pilih minimal 1 layanan atau isi qty produk lebih dari 0.';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->normalizeOptionalText($this->input('notes')),
            'services' => $this->normalizeServiceRows($this->input('services', [])),
            'products' => $this->normalizeProductRows($this->input('products', [])),
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'employee_id' => ['required', $this->activeEmployeeRule()],
            'payment_method' => ['required', 'in:cash,qr'],
            'notes' => ['nullable', 'string'],
            'services' => ['nullable', 'array'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $services = collect($this->input('services', []));
            $products = collect($this->input('products', []));

            if ($services->isEmpty() && $products->isEmpty()) {
                $validator->errors()->add(
                    'services',
                    self::MINIMUM_ITEM_MESSAGE
                );
            }

            $this->validateDuplicateIds(
                $validator,
                $products,
                'product_id',
                'products',
                'Produk duplikat ditemukan. Gunakan qty untuk menambah jumlah produk yang sama.'
            );
        });
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Pegawai wajib dipilih.',
            'employee_id.exists' => 'Pegawai nonaktif atau tidak valid untuk transaksi baru.',
        ];
    }

    private function normalizeServiceRows(mixed $services): array
    {
        return collect(is_array($services) ? $services : [])
            ->map(function ($row) {
                if (is_array($row)) {
                    $serviceId = $row['service_id'] ?? null;

                    if (! filled($serviceId)) {
                        return null;
                    }

                    return [
                        'service_id' => $serviceId,
                    ];
                }

                if (! filled($row)) {
                    return null;
                }

                return ['service_id' => $row];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeProductRows(mixed $products): array
    {
        return collect(is_array($products) ? $products : [])
            ->map(function ($row, $productId) {
                if (is_array($row)) {
                    $id = $row['product_id'] ?? null;
                    $qty = $row['qty'] ?? null;

                    if (! filled($id) && (! filled($qty) || (int) $qty === 1)) {
                        return null;
                    }

                    return [
                        'product_id' => $id,
                        'qty' => $qty,
                    ];
                }

                if (! is_numeric($productId)) {
                    return null;
                }

                if (! filled($row)) {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'qty' => $row,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeOptionalText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function validateDuplicateIds(
        Validator $validator,
        Collection $rows,
        string $key,
        string $errorKey,
        string $message
    ): void {
        $ids = $rows
            ->pluck($key)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->count() !== $ids->unique()->count()) {
            $validator->errors()->add($errorKey, $message);
        }
    }

    protected function activeEmployeeRule(?int $allowedEmployeeId = null): Exists
    {
        return Rule::exists('employees', 'id')->where(function (Builder $query) use ($allowedEmployeeId): void {
            $query->where('is_active', true);

            if ($allowedEmployeeId !== null) {
                $query->orWhere('id', $allowedEmployeeId);
            }
        });
    }
}
