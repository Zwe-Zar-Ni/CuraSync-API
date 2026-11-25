<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Public\Appointment\GenerateSlotRequest;
use App\Http\Resources\Doctor\ScheduleResource;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\Public\SpecializationResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialization;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends BaseController
{
    public function getSpecializations(): JsonResponse
    {
        $specializations = Specialization::get();

        return $this->success(SpecializationResource::collection($specializations));
    }

    public function getSpecializationDetails(int $id): JsonResponse
    {
        $specialization = Specialization::with(['doctors' => function ($q) {
            return $q->whereActive()->with('user.profile_image');
        }])->find($id);

        return $this->success(new SpecializationResource($specialization));
    }

    public function getDoctorDetails(int $id): JsonResponse
    {
        $doctor = Doctor::whereActive()->with(['user', 'specializations', 'qualifications'])->find($id);
        if (! $doctor) {
            return $this->error('Doctor not found', 404);
        }

        return $this->success(new DoctorResource($doctor));
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query('query', '');
        if (empty($q)) {
            return $this->error('Please enter a query', 400);
        }
        if (strlen($q) < 3) {
            return $this->error('Query too short', 400);
        }
        $specializations = Specialization::where('name', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')->get();

        $doctors = Doctor::whereActive()->whereHas('specializations', function ($query) use ($specializations) {
            $query->whereIn('specialization_id', $specializations->pluck('id'));
        })->orWhereHas('user', function ($query) use ($q) {
            $query->where('name', 'like', '%' . $q . '%')->orWhere('bio', 'like', '%' . $q . '%');
        })->with('user')->limit(10)->get();

        return $this->success([
            'specializations' => SpecializationResource::collection($specializations),
            'doctors' => DoctorResource::collection($doctors),
        ]);
    }

    public function doctorSchedules(int $id): JsonResponse
    {
        $doctor = Doctor::with(['schedules' => function ($query) {
            $query->where('is_active', true);
        }])->find($id);
        if (!$doctor) {
            return $this->error([], "Doctor not found.", 404);
        }
        $schedules = $doctor->schedules;
        if (count($schedules) == 0) {
            return $this->error([], "Doctor doesn't have active schedule.", 400);
        }
        $nextSchedules = [];
        foreach ($schedules as $schedule) {
            $dayOfWeek = (int) $schedule->day_of_week;
            $nextDate = now()->next($dayOfWeek);
            $nextSchedule = [
                'id' => $schedule->id,
                'date' => $nextDate,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'slot_duration_minutes' => $schedule->slot_duration_minutes
            ];
            array_push($nextSchedules, $nextSchedule);
        }
        return $this->success(collect($nextSchedules)->sortBy('date')->values()->all());
    }

    public function generateSlots(GenerateSlotRequest $request, int $id): JsonResponse
    {
        $schedule_id = $request->schedule_id;
        $date = $request->date;

        $doctor = Doctor::with(['schedules' => function ($query) {
            $query->where('is_active', true);
        }])->find($id);
        if (!$doctor) {
            return $this->error([], "Doctor not found.", 404);
        }

        $schedules = $doctor->schedules;
        if (count($schedules) == 0) {
            return $this->error([], "Doctor doesn't have active schedule.", 400);
        }
        $schedule = $schedules->filter(function ($sche) use ($schedule_id, $date) {
            $dayOfWeek = (int) $sche['day_of_week'];
            $nextDate = now()->next($dayOfWeek)->format('Y-m-d');
            return $schedule_id == $sche['id'] && $date == $nextDate;
        })->values()->first();
        if (!$schedule) {
            return $this->error([], "Selected schedule and date does not align or exist.", 400);
        }

        $slots = $this->generateSlotsFromSchedule($schedule, $date);
        return $this->success([
            'schedule' => new ScheduleResource($schedule),
            'slots' => $slots
        ]);
    }

    private function generateSlotsFromSchedule(mixed $schedule, mixed $date): array
    {
        $availableStatuses = ['CANCELLED', 'NO_SHOW'];
        $existingAppointments = Appointment::where('doctor_id', $schedule['doctor_id'])->whereDate('date', $date)->whereNotIn('status', $availableStatuses)->get();

        $cursor = Carbon::createFromFormat('H:i:s', $schedule['start_time']);
        $end = Carbon::createFromFormat('H:i:s', $schedule['end_time']);
        $duration = (int) $schedule['slot_duration_minutes'];

        $slots = [];

        while ($cursor->copy()->addMinutes($duration)->lte($end)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $isTaken = false;

            foreach ($existingAppointments as $key => $value) {
                if (
                    $value['start_time'] == $slotStart->format('H:i:s')  &&
                    $value['end_time'] == $slotEnd->format('H:i:s')
                ) {
                    $isTaken = true;
                }
            }

            $slots[] = [
                'start_time' => $slotStart->format('H:i'),
                'end_time'   => $slotEnd->format('H:i'),
                'is_taken' => $isTaken
            ];

            $cursor->addMinutes($duration);
        }

        return $slots;
    }
}
