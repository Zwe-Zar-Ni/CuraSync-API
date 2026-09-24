<?php

namespace App\Http\Requests\Doctor\ScheduleOverride;

use App\Enums\ScheduleOverrideType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'type' => ['required', Rule::enum(ScheduleOverrideType::class)],
            'start_time' => ['nullable', 'date_format:H:i', 'required_if:type,CUSTOM_HOURS'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_if:type,CUSTOM_HOURS', 'after:start_time'],
            'slot_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
