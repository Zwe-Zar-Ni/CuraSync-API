<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\DoctorSchedule;
use App\Http\Resources\Admin\ScheduleResource;

class ScheduleController extends BaseController
{
    public function index(Request $request)
    {
        $isActive = $request->filled('is_active') ? $request->is_active : true;
        $dayOfWeek = $request->filled('day_of_week') ? $request->day_of_week : "0";
        $schedules = DoctorSchedule::where('is_active', $isActive)->where('day_of_week', $dayOfWeek)->with('doctor.user')->orderBy('start_time')->get();
        return $this->success(ScheduleResource::collection($schedules));
    }
}
