<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? throw new Error('Doctor Not Found.');
    }

    public function index(Request $request): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $query = Appointment::where('doctor_id', $doctorId);
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $appointments = $query->latest()->paginate(20);
        return $this->success([
            "data" => AppointmentResource::collection($appointments),
            "meta" => $this->extractPaginationMeta($appointments)
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $today = Carbon::today();
        $appointments = Appointment::where('patient_id', $doctorId)->whereDate('date', '>=', $today)->whereIn('status', ['PENDING', 'CONFIRMED', 'CHECKED_IN', 'IN_PROGRESS'])->with('patient.user')->orderBy('date', 'desc')->orderBy('start_time', 'desc')->get();
        return $this->success(AppointmentResource::collection($appointments));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $appointment = Appointment::where('doctor_id', $doctorId)->with(['patient.user', 'patient.allergies', 'patient.conditions', 'patient.contacts'])->find($id);
        if (!$appointment) {
            return $this->error([], "Appointment Not found.", 404);
        }
        return $this->success(new AppointmentResource($appointment));
    }

    public function appointmentsByPatient(Request $request, int $patient_id): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $patient = Patient::with('user')->find($patient_id);
        if (!$patient) {
            return $this->error([], 'Patient Not Found.', 404);
        }
        $appointments = Appointment::where('patient_id', $patient->id)->where('doctor_id', $doctorId)->latest()->get();
        return $this->success([
            'appointments' => AppointmentResource::collection($appointments),
            'patient' => new PatientResource($patient)
        ]);
    }

    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);
        $appointment = Appointment::where('doctor_id', $doctorId)->find($id);

        if (!$appointment) {
            return $this->error([], "Appointment not found.", 404);
        }

        if (
            $appointment->status == AppointmentStatus::Completed
            || $appointment->status == AppointmentStatus::Cancelled
            || $appointment->status == AppointmentStatus::NoShow
        ) {
            return $this->error([], "Appointment cannot be updated.", 400);
        }

        $appointment->status = $req['status'];
        $appointment->update();
        return $this->success([], "Appointment status has been updated.");
    }
}
