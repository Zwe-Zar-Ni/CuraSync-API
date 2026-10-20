<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Doctor\ScheduleRequestResource;
use Illuminate\Http\Request;
use App\Models\DoctorScheduleRequest;

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
        $requests = $query->paginate($request->per_page ?? 10);
        return $this->success([
            'data' => ScheduleRequestResource::collection($requests),
            'meta' => $this->extractPaginationMeta($requests),
        ]);
    }
}
