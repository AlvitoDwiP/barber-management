<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithCommissionOverride;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    use InteractsWithCommissionOverride;

    protected function allowedCommissionTypes(): array
    {
        return ['percent'];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCommissionOverrideForValidation();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', ...$this->positiveMoneyRules('Harga')],
            ...$this->commissionOverrideRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'price.decimal' => 'Harga maksimal boleh memiliki 2 angka desimal.',
            ...$this->commissionOverrideMessages(),
        ];
    }
}
