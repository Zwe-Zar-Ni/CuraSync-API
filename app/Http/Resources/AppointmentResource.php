<?php

namespace App\Http\Resources;

use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->whenLoaded('doctor', function ($doctor) {
            return DoctorResource::make($doctor);
        });
        $patient = $this->whenLoaded('patient', function ($patient) {
            return PatientResource::make($patient);
        });
        return [
            'id' => $this->id,
            'status' => $this->status,
            'doctor_id' => $this->doctor_id,
            'patient_id' => $this->patient_id,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'reason_for_visit' => $this->reason_for_visit,
            'consultation_fee' => $this->consultation_fee,
            'created_at' => $this->created_at,
            'doctor' => $doctor,
            'patient' => $patient
        ];
    }
}
