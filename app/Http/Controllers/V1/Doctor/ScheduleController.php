<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Schedule\StoreScheduleRequest;
use App\Http\Requests\Doctor\Schedule\UpdateScheduleRequest;
use App\Http\Resources\Doctor\ScheduleResource as DoctorScheduleResource;
use App\Models\DoctorSchedule;
use App\Models\DoctorScheduleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\ScheduleRequestType;
use App\Enums\ScheduleRequestStatus;

class ScheduleController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $schedules = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->latest()->get();

        return $this->success(DoctorScheduleResource::collection($schedules));
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);

        $schedule_request = new DoctorScheduleRequest();
        $schedule_request->doctor_id = $doctorId;
        $schedule_request->request_type = ScheduleRequestType::CREATE;
        $schedule_request->status = ScheduleRequestStatus::PENDING;
        $schedule_request->doctor_note = $req['doctor_note'] ?? null;
        $schedule_request->data = [
            'day_of_week' => $req['day_of_week'],
            'start_time' => $req['start_time'],
            'end_time' => $req['end_time'],
            'slot_duration_minutes' => $req['slot_duration_minutes'] ?? 15,
        ];
        $schedule_request->save();

        return $this->success([], 'Schedule has been created for approval.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }

        return $this->success(new DoctorScheduleResource($schedule));
    }

    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }

        $schedule_request = new DoctorScheduleRequest();
        $schedule_request->doctor_id = $doctorId;
        $schedule_request->schedule_id = $schedule->id;
        $schedule_request->request_type = ScheduleRequestType::UPDATE;
        $schedule_request->status = ScheduleRequestStatus::PENDING;
        $schedule_request->doctor_note = $request['doctor_note'] ?? null;
        $schedule_request->data = $request->validated();
        $schedule_request->save();

        return $this->success([], 'Schedule update has been requested for approval.', 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)->find($id);
        if (! $schedule) {
            return $this->error('Schedule not found.', 404);
        }
        $schedule_request = new DoctorScheduleRequest();
        $schedule_request->doctor_id = $doctorId;
        $schedule_request->schedule_id = $schedule->id;
        $schedule_request->request_type = ScheduleRequestType::DELETE;
        $schedule_request->status = ScheduleRequestStatus::PENDING;
        $schedule_request->doctor_note = $request->input('doctor_note');
        $schedule_request->save();

        return $this->success([], 'Schedule delete has been requested for approval.', 200);
    }
}
