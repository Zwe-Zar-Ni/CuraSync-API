<?php

namespace App\Http\Controllers\V1\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with('doctor.user');
        if ($request->filled('patient_id')) {
            $query->whereDate('patient_id', $request->patient_id);
        }
        if ($request->filled('doctor_id')) {
            $query->whereDate('doctor_id', $request->doctor_id);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $appointments = $query->latest()->paginate($request->query('per_page', 10));
        return $this->success([
            "data" => $appointments,
            "meta" => $this->extractPaginationMeta($appointments)
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->find($id);
        if (!$appointment) {
            return $this->error([], "Appointment not found.", 404);
        }
        return $this->success($appointment);
    }

    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $req = $request->validated();
        $appointment = Appointment::find($id);

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
