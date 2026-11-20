<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'blood_type' => $this->blood_type,
            'name' => $this->whenLoaded('user', function () {
                return $this->user->name;
            }),
            'email' => $this->whenLoaded('user', function () {
                return $this->user->email;
            }),
            'phone_number' => $this->whenLoaded('user', function () {
                return $this->user->phone_number;
            }),
            'profile_image' => $this->whenLoaded('user', function () {
                return $this->user->profile_image ? $this->user->profile_image->url : null;
            }),
        ];
    }
}
