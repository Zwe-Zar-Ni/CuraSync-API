<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\Qualification\StoreQualificationRequest;
use App\Http\Requests\Doctor\Qualification\UpdateQualificationRequest;
use App\Http\Resources\Doctor\QualificationResource;
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

        return $this->success(QualificationResource::collection($qualifications));
    }

    public function store(StoreQualificationRequest $request): JsonResponse
    {
        $req = $request->validated();
        $doctorId = $this->getDoctorId($request);

        $path = null;
        if ($request->file('certificate_file')) {
            $path = $request->file('certificate_file')->store('certificates', 'public');
        }

        $qualification = DoctorQualification::create([
            'doctor_id' => $doctorId,
            'name' => $req['name'],
            'institution' => $req['institution'],
            'year' => $req['year'],
            'certificate_url' => $path ?? null,
        ]);

        return $this->success(new QualificationResource($qualification), 'Qualification created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $qualification = DoctorQualification::where('doctor_id', $this->getDoctorId($request))->find($id);
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

        $path = $qualification->certificate_url;
        if ($request->file('certificate_file')) {
            $filepath = $request->file('certificate_file')->store('certificates', 'public');
            if ($filepath) {
                $path = $filepath;
            }
        }

        $qualification->name = $req['name'];
        $qualification->institution = $req['institution'];
        $qualification->year = $req['year'];
        $qualification->certificate_url = $path;
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
