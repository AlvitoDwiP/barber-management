<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreDailyBatchTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $entries = collect($this->input('entries', []))
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return $entry;
                }

                return [
                    'notes' => $this->normalizeOptionalText($entry['notes'] ?? null),
                    'payment_method' => $entry['payment_method'] ?? null,
                    'services' => $this->normalizeServiceRows($entry['services'] ?? []),
                    'products' => $this->normalizeProductRows($entry['products'] ?? []),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'transaction_date' => $this->input('transaction_date'),
            'employee_id' => $this->input('employee_id'),
            'entries' => $entries,
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'employee_id' => ['required', 'exists:employees,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['required', 'array'],
            'entries.*.notes' => ['nullable', 'string'],
            'entries.*.payment_method' => ['required', 'in:cash,qr'],
            'entries.*.services' => ['nullable', 'array'],
            'entries.*.services.*' => ['required', 'array'],
            'entries.*.services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'entries.*.products' => ['nullable', 'array'],
            'entries.*.products.*' => ['required', 'array'],
            'entries.*.products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'entries.*.products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! is_array($this->input('entries'))) {
                return;
            }

            foreach ($this->input('entries', []) as $index => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $services = collect($entry['services'] ?? []);
                $products = collect($entry['products'] ?? []);

                if ($services->isEmpty() && $products->isEmpty()) {
                    $validator->errors()->add(
                        "entries.{$index}.items",
                        'Transaksi '.$this->entryNumber($index).' harus berisi minimal 1 layanan atau 1 produk.'
                    );
                }

                $this->validateDuplicateIds(
                    $validator,
                    $products,
                    'product_id',
                    "entries.{$index}.products",
                    'Produk duplikat ditemukan pada transaksi '.$this->entryNumber($index).'. Gunakan qty untuk menambah jumlah produk yang sama.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi tidak valid.',
            'employee_id.required' => 'Pegawai wajib dipilih.',
            'employee_id.exists' => 'Pegawai yang dipilih tidak valid.',
            'entries.required' => 'Tambahkan minimal 1 transaksi.',
            'entries.min' => 'Tambahkan minimal 1 transaksi.',
            'entries.*.array' => 'Format transaksi harian tidak valid.',
            'entries.*.payment_method.required' => 'Metode pembayaran wajib dipilih untuk setiap transaksi.',
            'entries.*.payment_method.in' => 'Metode pembayaran transaksi harus cash atau qr.',
            'entries.*.services.*.array' => 'Format baris layanan tidak valid.',
            'entries.*.services.*.service_id.required' => 'Pilih layanan pada baris layanan yang diisi.',
            'entries.*.services.*.service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'entries.*.products.*.array' => 'Format baris produk tidak valid.',
            'entries.*.products.*.product_id.required' => 'Pilih produk pada baris produk yang diisi.',
            'entries.*.products.*.product_id.exists' => 'Produk yang dipilih tidak valid.',
            'entries.*.products.*.qty.required' => 'Qty produk wajib diisi.',
            'entries.*.products.*.qty.integer' => 'Qty produk harus berupa angka bulat.',
            'entries.*.products.*.qty.min' => 'Qty produk minimal 1.',
        ];
    }

    private function normalizeServiceRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                $serviceId = $row['service_id'] ?? null;

                if (! filled($serviceId)) {
                    return null;
                }

                return [
                    'service_id' => $serviceId,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeProductRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                $productId = $row['product_id'] ?? null;
                $qty = $row['qty'] ?? 1;

                if (! filled($productId) && (! filled($qty) || (int) $qty === 1)) {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'qty' => $qty,
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

    private function entryNumber(int $index): int
    {
        return $index + 1;
    }
}
