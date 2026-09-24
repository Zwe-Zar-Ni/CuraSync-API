<?php

namespace App\Http\Controllers\V1\Patient;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Patient\Contact\StoreContactRequest;
use App\Http\Requests\Patient\Contact\UpdateContactRequest;
use App\Models\PatientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends BaseController
{
    private function getPatientId(Request $request): int
    {
        return $request->user()->patient?->id ?? 0;
    }

    public function index(Request $request): JsonResponse
    {
        $contacts = PatientContact::where('patient_id', $this->getPatientId($request))->latest()->get();

        return $this->success($contacts);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $req = $request->validated();
        $patientId = $this->getPatientId($request);
        $contact = PatientContact::create([
            'patient_id' => $patientId,
            'name' => $req['name'],
            'phone_number' => $req['phone_number'],
            'email' => $req['email'],
            'address' => $req['address'],
        ]);

        return $this->success($contact, 'Contact created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $contact = PatientContact::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $contact) {
            return $this->error('Contact not found.', 404);
        }

        return $this->success($contact);
    }

    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        $contact = PatientContact::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $contact) {
            return $this->error('Contact not found.', 404);
        }
        $contact->update($request->validated());

        return $this->success($contact, 'Contact updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $contact = PatientContact::where('patient_id', $this->getPatientId($request))->find($id);
        if (! $contact) {
            return $this->error('Contact not found.', 404);
        }
        $contact->delete();

        return $this->success([], 'Contact deleted successfully.');
    }
}
