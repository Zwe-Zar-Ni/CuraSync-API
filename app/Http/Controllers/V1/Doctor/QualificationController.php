<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Qualification\StoreQualificationRequest;
use App\Http\Requests\Doctor\Qualification\UpdateQualificationRequest;
use App\Models\DoctorQualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $qualifications = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->latest()->get();

        return $this->success($qualifications);
    }

    public function store(StoreQualificationRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);
        $qualification = DoctorQualification::create([
            'doctor_id' => $doctorId,
            'name' => $req['name'],
            'institution' => $req['institution'],
            'year' => $req['year'],
            'certificate_url' => $req['certificate_url'],
        ]);

        return $this->success($qualification, 'Qualification created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }

        return $this->success($qualification);
    }

    public function update(UpdateQualificationRequest $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }
        $qualification->update($request->validated());

        return $this->success($qualification, 'Qualification updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }
        $qualification->delete();

        return $this->success([], 'Qualification deleted successfully.');
    }
}
