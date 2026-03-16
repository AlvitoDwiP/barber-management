<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('employment_type') && $this->filled('status')) {
            $this->merge([
                'employment_type' => $this->input('status') === 'tetap'
                    ? Employee::EMPLOYMENT_TYPE_PERMANENT
                    : $this->input('status'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(Employee::employmentTypes())],
        ];
    }
}
