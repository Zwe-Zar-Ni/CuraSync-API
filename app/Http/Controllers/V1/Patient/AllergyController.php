<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Patient\Allergy\StoreAllergyRequest;
use App\Http\Requests\Patient\Allergy\UpdateAllergyRequest;
use App\Models\PatientAllergy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllergyController extends BaseController
{
    private function getPatientId(Request $request): int
    {
        return $request->user()->patient?->id ?? 0;
    }
    public function index(Request $request): JsonResponse
    {
        $allergies = PatientAllergy::where('patient_id', $this->getPatientId($request))->latest()->get();
        return $this->success($allergies);
    }

    public function store(StoreAllergyRequest $request): JsonResponse
    {
        $req = $request->validated();
        $patientId = $this->getPatientId($request);
        $allergy = PatientAllergy::create([
            'patient_id' => $patientId,
            'name' => $req['name'],
            'severity' => $req['severity'],
            'note' => $req['note'] ?? null,
        ]);
        return $this->success($allergy, 'Allergy created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $allergy = PatientAllergy::where('patient_id', $this->getPatientId($request))->find($id);
        if (!$allergy) {
            return $this->error('Allergy not found.', 404);
        }
        return $this->success($allergy);
    }

    public function update(UpdateAllergyRequest $request, int $id): JsonResponse
    {
        $allergy = PatientAllergy::where('patient_id', $this->getPatientId($request))->find($id);
        if (!$allergy) {
            return $this->error('Allergy not found.', 404);
        }
        $allergy->update($request->validated());
        return $this->success($allergy, 'Allergy updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $allergy = PatientAllergy::where('patient_id', $this->getPatientId($request))->find($id);
        if (!$allergy) {
            return $this->error('Allergy not found.', 404);
        }
        $allergy->delete();
        return $this->success([], 'Allergy deleted successfully.');
    }
}
