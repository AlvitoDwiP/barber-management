<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Support\Transactions\TransactionItemPayload;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = TransactionItemPayload::normalizeItemizedTransactionPayload($this->all());

        $this->replace([
            ...$payload,
            'items' => collect($payload['items'] ?? [])
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    unset($item['commission_type'], $item['commission_value']);

                    return $item;
                })
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $employeeRule = Rule::exists('employees', 'id')->where(
            fn (Builder $query) => $query->where(function (Builder $employeeQuery): void {
                $employeeQuery->where('is_active', true);

                $currentEmployeeIds = $this->currentTransactionEmployeeIds();

                if ($currentEmployeeIds !== []) {
                    $employeeQuery->orWhereIn('id', $currentEmployeeIds);
                }
            })
        );

        return [
            'transaction_date' => ['required', 'date'],
            'employee_id' => ['required', 'integer', $employeeRule],
            'payment_method' => ['required', 'in:cash,qr'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.item_type' => ['required', 'in:service,product'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.employee_id' => ['required', 'integer', $employeeRule],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = collect($this->input('items', []));

            if ($items->isEmpty()) {
                $validator->errors()->add('items', 'Transaksi harus berisi minimal 1 item.');

                return;
            }

            foreach ($items as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (($item['item_type'] ?? null) === 'service') {
                    if (! filled($item['service_id'] ?? null)) {
                        $validator->errors()->add(
                            "items.{$itemIndex}.service_id",
                            'Pilih layanan untuk item layanan.'
                        );
                    }

                    if ((int) ($item['qty'] ?? 0) !== 1) {
                        $validator->errors()->add(
                            "items.{$itemIndex}.qty",
                            'Qty layanan harus 1.'
                        );
                    }
                }

                if (($item['item_type'] ?? null) === 'product' && ! filled($item['product_id'] ?? null)) {
                    $validator->errors()->add(
                        "items.{$itemIndex}.product_id",
                        'Pilih produk untuk item produk.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi tidak valid.',
            'employee_id.required' => 'Pegawai transaksi wajib dipilih.',
            'employee_id.exists' => 'Pegawai transaksi tidak valid atau sudah tidak tersedia.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran transaksi harus cash atau qr.',
            'items.required' => 'Tambahkan minimal 1 item transaksi.',
            'items.min' => 'Tambahkan minimal 1 item transaksi.',
            'items.*.array' => 'Format item transaksi tidak valid.',
            'items.*.item_type.required' => 'Tipe item wajib diisi.',
            'items.*.item_type.in' => 'Tipe item harus service atau product.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.employee_id.required' => 'Pegawai item wajib dipilih.',
            'items.*.employee_id.exists' => 'Pegawai item tidak valid atau sudah tidak tersedia.',
            'items.*.qty.required' => 'Qty item wajib diisi.',
            'items.*.qty.integer' => 'Qty item harus berupa angka bulat.',
            'items.*.qty.min' => 'Qty item minimal 1.',
        ];
    }

    private function currentTransactionEmployeeIds(): array
    {
        $transaction = $this->route('transaction');

        if (! $transaction instanceof Transaction) {
            return [];
        }

        return collect([$transaction->employee_id])
            ->merge(
                $transaction->transactionItems()
                    ->pluck('employee_id')
                    ->all()
            )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
