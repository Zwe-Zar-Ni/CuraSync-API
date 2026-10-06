<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorSpecialtyResource extends JsonResource
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
            'specialization_id' => $this->specialization_id,
            'doctor_id' => $this->doctor_id,
            'specialization' => $this->whenLoaded('specialization', function () {
                return [
                    'name' => $this->specialization->name,
                    'description' => $this->specialization->description,
                    'icon_url' => $this->specialization->icon_url,
                ];
            }),
        ];
    }
}
