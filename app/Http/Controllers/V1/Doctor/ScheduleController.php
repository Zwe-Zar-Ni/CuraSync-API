<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Schedule\StoreScheduleRequest;
use App\Http\Requests\Doctor\Schedule\UpdateScheduleRequest;
use App\Models\DoctorSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $schedules = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->latest()->get();

        return $this->success($schedules);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);
        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctorId,
            'day_of_week' => $req['day_of_week'],
            'start_time' => $req['start_time'],
            'end_time' => $req['end_time'],
            'slot_duration_minutes' => $req['slot_duration_minutes'],
            'is_active' => $req['is_active'],
        ]);

        return $this->success($schedule, 'Schedule created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }

        return $this->success($schedule);
    }

    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }
        $schedule->update($request->validated());

        return $this->success($schedule, 'Schedule updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }
        $schedule->delete();

        return $this->success([], 'Schedule deleted successfully.');
    }
}
