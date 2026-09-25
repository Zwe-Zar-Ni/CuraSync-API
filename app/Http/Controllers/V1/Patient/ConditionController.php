<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Patient\Condition\StoreConditionRequest;
use App\Http\Requests\Patient\Condition\UpdateConditionRequest;
use App\Models\PatientCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConditionController extends BaseController
{
    private function getPatientId(Request $request): int
    {
        return $request->user()->patient?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $conditions = PatientCondition::where('patient_id', $this->getPatientId($request))->latest()->get();

        return $this->success($conditions);
    }

    public function store(StoreConditionRequest $request): JsonResponse
    {
        $req = $request->validated();
        $patientId = $this->getPatientId($request);
        $condition = PatientCondition::create([
            'patient_id' => $patientId,
            'name' => $req['name'],
            'diagnosis_date' => $req['diagnosis_date'] ?? null,
            'status' => $req['status'],
            'note' => $req['note'] ?? null,
        ]);

        return $this->success($condition, 'Condition created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $condition = PatientCondition::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $condition) {
            return $this->error('Condition not found.', 404);
        }

        return $this->success($condition);
    }

    public function update(UpdateConditionRequest $request, int $id): JsonResponse
    {
        $condition = PatientCondition::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $condition) {
            return $this->error('Condition not found.', 404);
        }
        $condition->update($request->validated());

        return $this->success($condition, 'Condition updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $condition = PatientCondition::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $condition) {
            return $this->error('Condition not found.', 404);
        }
        $condition->delete();

        return $this->success([], 'Condition deleted successfully.');
    }
}
