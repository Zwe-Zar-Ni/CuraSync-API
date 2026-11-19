<?php

namespace App\Http\Requests\Admin\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "status" => "required|string|in:IN_PROGRESS,COMPLETED,NO_SHOW,CHECKED_IN,CANCELLED"
        ];
    }
}
