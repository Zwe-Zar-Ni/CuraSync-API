<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $qualifications = $this->whenLoaded('qualifications', function () {
            return $this->qualifications->map(function ($qualification) {
                return [
                    'name' => $qualification->name,
                    'institution' => $qualification->institution,
                    'year' => $qualification->year,
                ];
            });
        });

        $specializations = $this->whenLoaded('specializations', function () {
            return $this->specializations->map(function ($spec) {
                return [
                    'name' => $spec->name,
                    'description' => $spec->description,
                    'icon_url' => $spec->icon_url,
                ];
            });
        });

        $schedules = $this->whenLoaded('schedules', function () {
            return $this->schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'slot_duration_minutes' => $schedule->slot_duration_minutes,
                ];
            });
        });

        $schedule_overrides = $this->whenLoaded('scheduleOverrides', function () {
            return $this->scheduleOverrides->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date,
                    'type' => $schedule->type,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'slot_duration_minutes' => $schedule->slot_duration_minutes,
                ];
            });
        });

        return [
            'id' => $this->id,
            'license_number' => $this->license_number,
            'standard_consultation_fee' => (float) $this->standard_consultation_fee,
            'bio' => $this->bio,
            'total_patient_count' => $this->total_patient_count,
            'rating_count' => $this->rating_count,
            'average_rating' => (float) $this->average_rating,
            'specilizations' => $specializations,
            'qualifications' => $qualifications,
            'schedules' => $schedules,
            'schedule_overrides' => $schedule_overrides,
            'name' => $this->whenLoaded('user', function () {
                return $this->user->name;
            }),
            'email' => $this->whenLoaded('user', function () {
                return $this->user->email;
            }),
            'phone_number' => $this->whenLoaded('user', function () {
                return $this->user->phone_number;
            }),
            'profile_url' => $this->whenLoaded('user', function () {
                return $this->user->profile_url;
            }),
        ];
    }
}
