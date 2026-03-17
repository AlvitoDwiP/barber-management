<?php

namespace App\Http\Requests;

use App\Models\Transaction;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        /** @var Transaction|null $transaction */
        $transaction = $this->route('transaction');
        $allowedEmployeeId = $transaction?->employee_id;

        return [
            'transaction_date' => ['required', 'date'],
            'employee_id' => ['required', $this->activeEmployeeRule($allowedEmployeeId)],
            'payment_method' => ['required', 'in:cash,qr'],
            'notes' => ['nullable', 'string'],
            'services' => ['nullable', 'array'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
