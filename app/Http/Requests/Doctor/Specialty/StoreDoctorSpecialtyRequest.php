<?php

namespace App\Http\Requests\Doctor\Specialty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specialization_id' => [
                'required',
                'integer',
                'exists:specializations,id',
                Rule::unique('doctor_specialty', 'specialization_id')->where('doctor_id', $this->user()->doctor?->id),
            ],
        ];
    }
}
