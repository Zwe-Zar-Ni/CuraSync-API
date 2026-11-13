<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Doctor\ScheduleResource as DoctorScheduleResource;
use App\Models\DoctorSchedule;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? throw new Error('Doctor Not Found.');
    }

    public function index(Request $request): JsonResponse
    {
        $schedules = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->latest()->get();
        return $this->success(DoctorScheduleResource::collection($schedules));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }
        return $this->success(new DoctorScheduleResource($schedule));
    }
}
