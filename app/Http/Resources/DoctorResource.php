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
        return [
            'id' => $this->id,
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
            'license_number' => $this->license_number,
            'standard_consultation_fee' => (float) $this->standard_consultation_fee,
            'bio' => $this->bio,
            'total_patient_count' => $this->total_patient_count,
            'rating_count' => $this->rating_count,
            'average_rating' => (float) $this->average_rating,
            'specilizations' => $this->whenLoaded('specializations', function () {
                return $this->specializations->pluck('name')->toArray();
            }),
        ];
    }
}
