<?php

namespace App\Http\Controllers\V1\Patient;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\BaseController;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Patient\Appointment\StoreAppointmentRequest;
use App\Http\Resources\DoctorResource;

class AppointmentController extends BaseController
{
    private function getPatientId(Request $request): int
    {
        return $request->user()->patient?->id ?? throw new Error('Patient profile not found.');
    }

    public function index(Request $request): JsonResponse
    {
        $patientId = $this->getPatientId($request);
        $appointments = Appointment::where('patient_id', $patientId)->with('doctor.user')->latest()->paginate(20);
        return $this->success([
            "data" => AppointmentResource::collection($appointments),
            "meta" => $this->extractPaginationMeta($appointments)
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $patientId = $this->getPatientId($request);
        $today = Carbon::today();
        $appointments = Appointment::where('patient_id', $patientId)->whereDate('date', '>=', $today)->whereIn('status', ['PENDING', 'CONFIRMED', 'CHECKED_IN', 'IN_PROGRESS'])->with('doctor.user')->orderBy('date', 'desc')->orderBy('start_time', 'desc')->get();
        return $this->success(AppointmentResource::collection($appointments));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {

        $patientId = $this->getPatientId($request);
        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $startTime =  Carbon::createFromFormat('H:i', $request->start_time);
        $endTime =  Carbon::createFromFormat('H:i', $request->end_time);

        $doctor = Doctor::with(['schedules' => function ($query) {
            $query->where('is_active', true);
        }])->find($request->doctor_id);
        if (!$doctor) {
            return $this->error([], "Doctor not found.", 404);
        }

        $schedule = $doctor->schedules->firstWhere('day_of_week', $dayOfWeek);
        if (!$schedule) {
            return $this->error([],  'The doctor is not available on this day.', 422);
        }

        $scheduleStartTime = Carbon::createFromFormat('H:i+', $schedule['start_time']);
        $scheduleEndTime = Carbon::createFromFormat('H:i+', $schedule['end_time']);
        if ($startTime->lt($scheduleStartTime) || $endTime->gt($scheduleEndTime)) {
            return $this->error([], 'Selected time is outside the doctor working hours.', 422);
        }

        return DB::transaction(function () use ($request, $patientId, $startTime, $endTime, $doctor) {

            //? Check for existing appointment with row-level lock (`lockForUpdate`)
            $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
                ->where('date', $request->date)
                ->where('start_time', $startTime)
                ->whereNotIn('status', ['CANCELLED', 'NO_SHOW'])
                ->lockForUpdate()
                ->exists();

            if ($existingAppointment) {
                return $this->error([], 'This time slot has just been booked by another patient. Please select another slot.', 422);
            }

            //? Create the appointment securely
            Appointment::create([
                'doctor_id'        => $request->doctor_id,
                'patient_id'       => $patientId,
                'date'             => $request->date,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'status'           => AppointmentStatus::Confirmed,
                'reason_for_visit' => $request->reason_for_visit,
                'consultation_fee' => $doctor->standard_consultation_fee ?? 0,
            ]);

            return $this->success([], 'Appointment booked successfully.', 201);
        });
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $patientId = $this->getPatientId($request);
        $appointment = Appointment::where('patient_id', $patientId)->with('doctor.user')->find($id);
        if (!$appointment) {
            return $this->error([], "Appointment not found.", 404);
        }
        return $this->success(new AppointmentResource($appointment));
    }

    public function appointmentsByDoctor(Request $request, int $doctor_id): JsonResponse
    {
        $patientId = $this->getPatientId($request);
        $doctor = Doctor::with('user')->find($doctor_id);
        if (!$doctor) {
            return $this->error([], 'Doctor Not Found.', 404);
        }
        $appointments = Appointment::where('patient_id', $patientId)->where('doctor_id', $doctor->id)->latest()->get();
        return $this->success([
            'appointments' => AppointmentResource::collection($appointments),
            'doctor' => new DoctorResource($doctor)
        ]);
    }

    //! Cancels the appointment
    public function update(Request $request, int $id): JsonResponse
    {
        $patientId = $this->getPatientId($request);
        $appointment = Appointment::where('patient_id', $patientId)->find($id);
        if (!$appointment) {
            return $this->error([], "Appointment not found.", 404);
        }
        if ($appointment->status !== AppointmentStatus::Pending || $appointment->status !== AppointmentStatus::Confirmed) {
            return $this->error([], "Appointment cannot be cancelled.", 400);
        }
        $appointment->status = AppointmentStatus::Cancelled->value;
        $appointment->update();
        return $this->success([], "Appointment has been cancelled.");
    }
}
