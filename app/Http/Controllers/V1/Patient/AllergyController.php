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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PatientAllergy::class);

        $allergies = $request->user()->patient
            ->allergies()
            ->latest()
            ->get();

        return $this->success($allergies);
    }

    public function store(StoreAllergyRequest $request): JsonResponse
    {
        $this->authorize('create', PatientAllergy::class);

        $allergy = $request->user()->patient
            ->allergies()
            ->create($request->validated());

        return $this->success($allergy, 'Allergy created successfully.', 201);
    }

    public function show(Request $request, PatientAllergy $allergy): JsonResponse
    {
        $this->authorize('view', $allergy);

        return $this->success($allergy);
    }

    public function update(UpdateAllergyRequest $request, PatientAllergy $allergy): JsonResponse
    {
        $this->authorize('update', $allergy);

        $allergy->update($request->validated());

        return $this->success($allergy, 'Allergy updated successfully.');
    }

    public function destroy(Request $request, PatientAllergy $allergy): JsonResponse
    {
        $this->authorize('delete', $allergy);

        $allergy->delete();

        return $this->success([], 'Allergy deleted successfully.');
    }
}
