<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->getRoleNames()->first();

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'profile_url' => $this->profile_url,
            ],
            'role' => $role,
            'profile' => $role === 'doctor' ? [
                'id' => $this->doctor->id,
                'status' => $this->doctor->status,
                'license_number' => $this->doctor->license_number,
                'standard_consultation_fee' => (float) $this->doctor->standard_consultation_fee,
                'bio' => $this->doctor->bio,
                'total_patient_count' => $this->doctor->total_patient_count,
                'rating_count' => $this->doctor->rating_count,
                'average_rating' => (float) $this->doctor->average_rating,
            ] : [
                'id' => $this->patient->id,
                'date_of_birth' => $this->patient->date_of_birth,
                'gender' => $this->patient->gender,
                'blood_type' => $this->patient->blood_type,
            ],
        ];
    }
}
