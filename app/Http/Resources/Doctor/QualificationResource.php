<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualificationResource extends JsonResource
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
            'doctor_id' => $this->doctor_id,
            'name' => $this->name,
            'institution' => $this->institution,
            'year' => $this->year,
            'certificate_id' => $this->certificate_id,
            'certificate' => $this->whenLoaded('certificate', function () {
                return [
                    'id' => $this->certificate->id,
                    'url' => $this->certificate->url,
                    'mime_type' => $this->certificate->mime_type,
                ];
            })
        ];
    }
}
