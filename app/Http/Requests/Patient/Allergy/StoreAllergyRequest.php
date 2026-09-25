<?php

namespace App\Http\Requests\Patient\Allergy;

use App\Enums\AllergySeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::enum(AllergySeverity::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
