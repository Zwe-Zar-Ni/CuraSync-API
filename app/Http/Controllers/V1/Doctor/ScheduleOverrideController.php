<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\ScheduleOverride\StoreScheduleOverrideRequest;
use App\Http\Requests\Doctor\ScheduleOverride\UpdateScheduleOverrideRequest;
use App\Models\DoctorScheduleOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleOverrideController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $overrides = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->latest()->get();

        return $this->success($overrides);
    }

    public function store(StoreScheduleOverrideRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);
        $override = DoctorScheduleOverride::create([
            'doctor_id' => $doctorId,
            'date' => $req['date'],
            'type' => $req['type'],
            'start_time' => $req['start_time'],
            'end_time' => $req['end_time'],
            'slot_duration_minutes' => $req['slot_duration_minutes'],
            'reason' => $req['reason'],
        ]);

        return $this->success($override, 'Schedule override created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $override = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }

        return $this->success($override);
    }

    public function update(UpdateScheduleOverrideRequest $request, int $id): JsonResponse
    {
        $override = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }
        $override->update($request->validated());

        return $this->success($override, 'Schedule override updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $override = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }
        $override->delete();

        return $this->success([], 'Schedule override deleted successfully.');
    }
}
