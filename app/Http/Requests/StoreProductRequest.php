<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithCommissionOverride;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use InteractsWithCommissionOverride;

    protected function allowedCommissionTypes(): array
    {
        return ['percent', 'fixed'];
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
            'price' => ['required', 'numeric', 'gt:0'],
            'stock' => ['required', 'integer', 'min:0'],
            ...$this->commissionOverrideRules(),
        ];
    }

    public function messages(): array
    {
        return $this->commissionOverrideMessages();
    }
}
