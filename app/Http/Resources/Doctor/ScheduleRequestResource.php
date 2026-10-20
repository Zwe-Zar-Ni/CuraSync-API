<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\ProfileResource;
use App\Http\Resources\Public\DoctorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->whenLoaded('doctor', function ($q) {
            return DoctorResource::make($q);
        });
        $user = $this->whenLoaded('user', function ($q) {
            return ProfileResource::make($q);
        });
        $doctorSchedule = $this->whenLoaded('doctorSchedule', function ($q) {
            return ScheduleResource::make($q);
        });
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor_schedule_id' => $this->doctor_schedule_id,
            'request_type' => $this->request_type,
            'data' => $this->data,
            'doctor_note' => $this->doctor_note,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'reviewed_at' => $this->reviewed_at,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $user,
            'doctor' => $doctor,
            'doctor_schedule' => $doctorSchedule,
        ];
    }
}
