<?php

namespace App\Http\Requests\Doctor\Qualification;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'institution' => ['required', 'string', 'max:200'],
            'year' => ['required', 'integer', 'digits:4'],
            'certificate_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
