<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Enums\ScheduleRequestStatus;
use App\Enums\ScheduleRequestType;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\ScheduleOverride\StoreScheduleOverrideRequest;
use App\Http\Requests\Doctor\ScheduleOverride\UpdateScheduleOverrideRequest;
use App\Http\Resources\Doctor\ScheduleOverrideResource;
use App\Models\DoctorScheduleOverrideRequest;
use App\Models\DoctorScheduleOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleOverrideController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $lastWeek = Carbon::today()->subWeek();
        $overrides = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->whereDate('date', '>', $lastWeek)->latest()->get();

        return $this->success(ScheduleOverrideResource::collection($overrides));
    }

    public function store(StoreScheduleOverrideRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);

        $overrideRequest = new DoctorScheduleOverrideRequest();
        $overrideRequest->doctor_id = $doctorId;
        $overrideRequest->request_type = ScheduleRequestType::CREATE;
        $overrideRequest->status = ScheduleRequestStatus::PENDING;
        $overrideRequest->doctor_note = $req['doctor_note'] ?? null;
        $overrideRequest->data = [
            'doctor_id' => $doctorId,
            'date' => $req['date'],
            'type' => $req['type'],
            'start_time' => $req['start_time'] ?? null,
            'end_time' => $req['end_time'] ?? null,
            'slot_duration_minutes' => $req['slot_duration_minutes'] ?? 15,
            'reason' => $req['reason'] ?? null,
        ];
        $overrideRequest->save();

        return $this->success([], 'Please wait for approval.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $override = DoctorScheduleOverride::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }

        return $this->success(new ScheduleOverrideResource($override));
    }

    public function update(UpdateScheduleOverrideRequest $request, int $id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $nextFiveDays = Carbon::today()->addDays(5);
        $override = DoctorScheduleOverride::where('doctor_id', $doctorId)->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }
        if ($nextFiveDays->lessThan($override->date)) {
            return $this->error('Schedule override cannot be updated.', 400);
        }

        $overrideRequest = new DoctorScheduleOverrideRequest();
        $overrideRequest->doctor_id = $doctorId;
        $overrideRequest->schedule_id = $override->id;
        $overrideRequest->request_type = ScheduleRequestType::UPDATE;
        $overrideRequest->status = ScheduleRequestStatus::PENDING;
        $overrideRequest->doctor_note = $request['doctor_note'] ?? null;
        $overrideRequest->data = $request->validated();
        $overrideRequest->save();

        return $this->success([], 'Please wait for approval.', 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $nextFiveDays = Carbon::today()->addDays(5);
        $override = DoctorScheduleOverride::where('doctor_id', $doctorId)->find($id);
        if (! $override) {
            return $this->error('Schedule override not found.', 404);
        }
        if ($nextFiveDays->lessThan($override->date)) {
            return $this->error('Schedule override cannot be deleted.', 400);
        }

        $overrideRequest = new DoctorScheduleOverrideRequest();
        $overrideRequest->doctor_id = $doctorId;
        $overrideRequest->schedule_id = $override->id;
        $overrideRequest->request_type = ScheduleRequestType::DELETE;
        $overrideRequest->status = ScheduleRequestStatus::PENDING;
        $overrideRequest->doctor_note = $request->input('doctor_note');
        $overrideRequest->save();

        return $this->success([], 'Please wait for approval.', 204);
    }
}
