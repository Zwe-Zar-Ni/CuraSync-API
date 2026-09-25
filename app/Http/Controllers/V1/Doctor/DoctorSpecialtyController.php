<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Specialty\StoreDoctorSpecialtyRequest;
use App\Models\DoctorSpecialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorSpecialtyController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $specialties = DoctorSpecialty::where('doctor_id', $this->getDoctorId($request))->with('specialization')->latest()->get();

        return $this->success($specialties);
    }

    public function store(StoreDoctorSpecialtyRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);
        $specialty = DoctorSpecialty::create([
            'doctor_id' => $doctorId,
            'specialization_id' => $req['specialization_id'],
        ]);

        return $this->success($specialty, 'Specialty assigned successfully.', 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $specialty = DoctorSpecialty::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $specialty) {
            return $this->error('Specialty not found.', 404);
        }
        $specialty->delete();

        return $this->success([], 'Specialty removed successfully.');
    }
}
