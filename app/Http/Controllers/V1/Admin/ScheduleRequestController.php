<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Doctor\ScheduleRequestResource;
use Illuminate\Http\Request;
use App\Models\DoctorScheduleRequest;
use App\Http\Requests\Admin\ScheduleRequest\UpdateScheduleRequest;
use App\Enums\ScheduleRequestType;
use App\Enums\ScheduleRequestStatus;
use App\Models\DoctorSchedule;
use Illuminate\Support\Facades\DB;
use Exception;

class ScheduleRequestController extends BaseController
{
    public function index(Request $request)
    {
        $query = DoctorScheduleRequest::with(['doctor.user', 'user', 'doctorSchedule']);
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('day_of_week')) {
            $query->where('data->day_of_week', $request->day_of_week);
        }
        $requests = $query->latest()->paginate($request->per_page ?? 10);
        return $this->success([
            'data' => ScheduleRequestResource::collection($requests),
            'meta' => $this->extractPaginationMeta($requests),
        ]);
    }

    public function update(UpdateScheduleRequest $r, int $id)
    {
        $req = $r->validated();
        $request = DoctorScheduleRequest::find($id);
        if (!$request) {
            return $this->error('Request not found');
        }
        if ($request->status !== ScheduleRequestStatus::PENDING) {
            return $this->error([
                'status' => $request->status,
                'pending' => ScheduleRequestStatus::PENDING
            ], 'Request is not pending', 400);
        }
        DB::beginTransaction();
        try {
            $request->update([
                'status' => $req['status'],
                'admin_note' => $req['admin_note'],
                'user_id' => $r->user()->id,
                'reviewed_at' => now()
            ]);
            if ($req['status'] === 'REJECTED') {
                DB::commit();
                return $this->success([], "Request rejected.", 200);
            }
            if ($request->request_type === ScheduleRequestType::CREATE) {
                $newSchedule = new DoctorSchedule();
                $newSchedule->doctor_id = $request->doctor_id;
                $newSchedule->day_of_week = $request->data['day_of_week'];
                $newSchedule->start_time = $request->data['start_time'];
                $newSchedule->end_time = $request->data['end_time'];
                $newSchedule->slot_duration_minutes = $request->data['slot_duration_minutes'];
                $newSchedule->is_active = true;
                $newSchedule->save();
            } else if ($request->request_type === ScheduleRequestType::UPDATE) {
                $schedule = DoctorSchedule::findOrFail($request->doctor_schedule_id);
                $schedule->day_of_week = $request->data['day_of_week'];
                $schedule->start_time = $request->data['start_time'];
                $schedule->end_time = $request->data['end_time'];
                $schedule->slot_duration_minutes = $request->data['slot_duration_minutes'];
                $schedule->is_active = true;
                $schedule->update();
            } else if ($request->request_type === ScheduleRequestType::DELETE) {
                $schedule = DoctorSchedule::findOrFail($request->doctor_schedule_id);
                $schedule->delete();
            } else {
                return $this->error([], "Invalid request type.", 400);
            }
            DB::commit();
            return $this->success([], "Schedule updated for doctor.", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
