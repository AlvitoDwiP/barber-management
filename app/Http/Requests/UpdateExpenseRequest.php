<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithExactMoneyValidation;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date' => 'Tanggal pengeluaran tidak valid.',
            'category.required' => 'Pilih kategori pengeluaran.',
            'category.in' => 'Kategori pengeluaran tidak tersedia.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.decimal' => 'Nominal pengeluaran maksimal boleh memiliki 2 angka desimal.',
            'note.string' => 'Catatan pengeluaran harus berupa teks.',
        ];
    }
}
