<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'employee_id' => ['required', 'exists:employees,id'],
            'payment_method' => ['required', 'in:cash,qr'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'distinct', 'exists:services,id'],
            'products' => ['nullable', 'array'],
            'products.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $services = $this->input('services', []);
            $products = $this->input('products', []);

            $hasSelectedService = is_array($services)
                && collect($services)->filter(fn ($id) => filled($id))->isNotEmpty();

            $hasSelectedProduct = is_array($products)
                && collect($products)->contains(fn ($qty) => (int) $qty > 0);

            if (! $hasSelectedService && ! $hasSelectedProduct) {
                $validator->errors()->add(
                    'services',
                    'Transaksi harus memiliki minimal satu layanan atau satu produk dengan qty lebih dari 0.'
                );
            }

            if (! is_array($products) || $products === []) {
                return;
            }

            $productIds = collect(array_keys($products))
                ->map(function ($id) {
                    if (! is_numeric($id)) {
                        return null;
                    }

                    return (int) $id;
                });

            if ($productIds->contains(null) || $productIds->contains(fn ($id) => $id <= 0)) {
                $validator->errors()->add('products', 'Format produk tidak valid.');

                return;
            }

            $uniqueProductIds = $productIds->unique()->values();
            $existingCount = Product::query()->whereIn('id', $uniqueProductIds)->count();

            if ($existingCount !== $uniqueProductIds->count()) {
                $validator->errors()->add('products', 'Terdapat produk yang tidak ditemukan.');
            }
        });
    }
}
