<?php

namespace App\Http\Requests\Patient\Condition;

use App\Enums\ConditionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'diagnosis_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ConditionStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
