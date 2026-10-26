<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecializationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctors = $this->whenLoaded('doctors', function ($doctors) {
            return $doctors->map(function ($doctor) {
                return DoctorResource::make($doctor);
            });
        });
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon_url' => $this->icon_url,
            'doctors' => $doctors,
        ];
    }
}
