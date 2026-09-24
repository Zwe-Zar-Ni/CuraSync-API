<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'standard_consultation_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
