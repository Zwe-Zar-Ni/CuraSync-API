<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\CustomApiException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\UpdateDoctorProfileRequest;
use App\Http\Requests\Patient\UpdatePatientProfileRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success($this->profilePayload($request->user()));
    }

    public function updatePatientProfile(UpdatePatientProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->updateUserProfile($user, $request->validated());
        if (!$user->patient) {
            throw new CustomApiException([], 404, 'Patient profile not found.');
        }
        $user->patient->update(
            $request->only(['date_of_birth', 'gender', 'blood_type'])
        );
        return $this->success($this->profilePayload($user), 'Patient profile updated successfully.');
    }

    public function updateDoctorProfile(UpdateDoctorProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->updateUserProfile($user, $request->validated());
        if (! $user->doctor) {
            throw new CustomApiException([], 404, 'Doctor profile not found.');
        }
        $user->doctor->update(
            $request->only(['license_number', 'standard_consultation_fee', 'bio'])
        );
        return $this->success($this->profilePayload($user), 'Doctor profile updated successfully.');
    }

    private function updateUserProfile(User $user, array $data): void
    {
        $user->name = $data['name'] ?? $user->name;
        $user->phone_number = $data['phone_number'] ?? $user->phone_number;
        $user->profile_url = $data['profile_url'] ?? $user->profile_url;
        $user->update();
    }

    private function profilePayload(User $user): array
    {
        $roles = $user->getRoleNames()->all();
        return [
            'user' => $user,
            'roles' => $roles,
            'profile' => collect($roles)->contains('patient') ? $user->patient : $user->doctor,
            // 'patient' => $user->patient,
            // 'doctor' => $user->doctor,
        ];
    }
}
