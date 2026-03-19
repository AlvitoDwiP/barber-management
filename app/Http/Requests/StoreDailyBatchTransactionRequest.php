<?php

namespace App\Http\Requests;

use App\Support\Transactions\TransactionItemPayload;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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

                return TransactionItemPayload::normalizeItemizedTransactionPayload($entry);
            })
            ->map(fn ($entry) => is_array($entry) ? $this->stripCommissionOverrides($entry) : $entry)
            ->values()
            ->all();

        $this->merge([
            'transaction_date' => $this->input('transaction_date'),
            'entries' => $entries,
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['required', 'array'],
            'entries.*.employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn (Builder $query) => $query->where('is_active', true)),
            ],
            'entries.*.notes' => ['nullable', 'string'],
            'entries.*.payment_method' => ['required', 'in:cash,qr'],
            'entries.*.items' => ['required', 'array', 'min:1'],
            'entries.*.items.*' => ['required', 'array'],
            'entries.*.items.*.item_type' => ['required', 'in:service,product'],
            'entries.*.items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'entries.*.items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'entries.*.items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('entries', []) as $entryIndex => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $items = collect($entry['items'] ?? []);

                if ($items->isEmpty()) {
                    $validator->errors()->add(
                        "entries.{$entryIndex}.items",
                        'Transaksi '.($entryIndex + 1).' harus berisi minimal 1 item.'
                    );

                    continue;
                }

                foreach ($items as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    if (($item['item_type'] ?? null) === 'service') {
                        if (! filled($item['service_id'] ?? null)) {
                            $validator->errors()->add(
                                "entries.{$entryIndex}.items.{$itemIndex}.service_id",
                                'Pilih layanan untuk item layanan.'
                            );
                        }

                        if ((int) ($item['qty'] ?? 0) !== 1) {
                            $validator->errors()->add(
                                "entries.{$entryIndex}.items.{$itemIndex}.qty",
                                'Qty layanan harus 1.'
                            );
                        }
                    }

                    if (($item['item_type'] ?? null) === 'product' && ! filled($item['product_id'] ?? null)) {
                        $validator->errors()->add(
                            "entries.{$entryIndex}.items.{$itemIndex}.product_id",
                            'Pilih produk untuk item produk.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi tidak valid.',
            'entries.required' => 'Tambahkan minimal 1 transaksi.',
            'entries.min' => 'Tambahkan minimal 1 transaksi.',
            'entries.*.array' => 'Format transaksi harian tidak valid.',
            'entries.*.employee_id.required' => 'Pegawai transaksi wajib dipilih untuk setiap blok.',
            'entries.*.employee_id.exists' => 'Pegawai transaksi nonaktif atau tidak valid.',
            'entries.*.payment_method.required' => 'Metode pembayaran wajib dipilih untuk setiap transaksi.',
            'entries.*.payment_method.in' => 'Metode pembayaran transaksi harus cash atau qr.',
            'entries.*.items.*.item_type.required' => 'Tipe item wajib diisi.',
            'entries.*.items.*.item_type.in' => 'Tipe item harus service atau product.',
            'entries.*.items.*.service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'entries.*.items.*.product_id.exists' => 'Produk yang dipilih tidak valid.',
            'entries.*.items.*.qty.required' => 'Qty item wajib diisi.',
            'entries.*.items.*.qty.integer' => 'Qty item harus berupa angka bulat.',
            'entries.*.items.*.qty.min' => 'Qty item minimal 1.',
        ];
    }

    private function stripCommissionOverrides(array $entry): array
    {
        return [
            ...$entry,
            'items' => collect($entry['items'] ?? [])
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    unset($item['commission_type'], $item['commission_value']);

                    return $item;
                })
                ->values()
                ->all(),
        ];
    }
}
