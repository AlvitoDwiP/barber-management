<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithExactMoneyValidation;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    use InteractsWithExactMoneyValidation;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::in(Expense::categories())],
            'amount' => ['required', ...$this->positiveMoneyRules('Jumlah nominal')],
            'note' => ['nullable', 'string'],
            'freelance_payment_id' => ['nullable', 'integer', 'exists:freelance_payments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.decimal' => 'Jumlah nominal maksimal boleh memiliki 2 angka desimal.',
        ];
    }
}
