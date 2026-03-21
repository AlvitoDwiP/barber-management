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
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date' => 'Tanggal pengeluaran tidak valid.',
            'category.required' => 'Pilih kategori pengeluaran.',
            'category.in' => 'Kategori pengeluaran tidak tersedia.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.decimal' => 'Nominal pengeluaran maksimal boleh memiliki 2 angka desimal.',
            'note.string' => 'Catatan pengeluaran harus berupa teks.',
            'freelance_payment_id.integer' => 'Data pembayaran freelance tidak valid.',
            'freelance_payment_id.exists' => 'Pembayaran freelance tidak ditemukan.',
        ];
    }
}
