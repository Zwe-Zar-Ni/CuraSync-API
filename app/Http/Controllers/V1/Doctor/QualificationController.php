<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Qualification\StoreQualificationRequest;
use App\Http\Requests\Doctor\Qualification\UpdateQualificationRequest;
use App\Http\Resources\Doctor\QualificationResource;
use App\Models\DoctorQualification;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationController extends BaseController
{
    private function getDoctorId(Request $request): int
    {
        return $request->user()->doctor?->id ?? throw new Error('Doctor Not Found.');
    }

    public function index(Request $request): JsonResponse
    {
        $doctorId = $this->getDoctorId($request);
        $qualifications = DoctorQualification::where('doctor_id', $doctorId)->latest()->get();

        return $this->success(QualificationResource::collection($qualifications));
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
            'certificate_id' => $req['certificate_id'] ?? null,
        ]);

        return $this->success(new QualificationResource($qualification), 'Qualification created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->with('certificate')->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }

        return $this->success(new QualificationResource($qualification));
    }

    public function update(UpdateQualificationRequest $request, int $id): JsonResponse
    {
        $req = $request->validated();
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }

        $qualification->name = $req['name'];
        $qualification->institution = $req['institution'];
        $qualification->year = $req['year'];
        $qualification->certificate_id = $req['certificate_id'] ?? $qualification->certificate_id;
        $qualification->update();

        return $this->success(new QualificationResource($qualification), 'Qualification updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
        if (! $qualification) {
            return $this->error('Qualification not found.', 404);
        }
        $qualification->delete();

        return $this->success([], 'Qualification deleted successfully.', 204);
    }
}
