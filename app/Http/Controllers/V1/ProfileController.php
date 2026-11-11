<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\UpdateDoctorProfileRequest;
use App\Http\Requests\Patient\UpdatePatientProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(new ProfileResource($request->user()->load('doctor', 'patient')), 'Profile fetched successfully.');
    }

    public function updatePatientProfile(UpdatePatientProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->updateUserProfile($user, $request->validated());
        if (! $user->patient) {
            return $this->error('Patient profile not found.', 404);
        }
        $user->patient->update(
            $request->only(['date_of_birth', 'gender', 'blood_type'])
        );

        if ($request->file('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            if ($path) {
                $user->profile_url = $path;
                $user->update();
            }
        }

        return $this->success(new ProfileResource($user), 'Patient profile updated successfully.');
    }

    public function updateDoctorProfile(UpdateDoctorProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->updateUserProfile($user, $request->validated());
        if (! $user->doctor) {
            return $this->error('Doctor profile not found.', 404);
        }
        $user->doctor->update(
            $request->only(['license_number', 'standard_consultation_fee', 'bio'])
        );

        if ($request->file('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            if ($path) {
                $user->profile_url = $path;
                $user->update();
            }
        }

        return $this->success(new ProfileResource($user), 'Doctor profile updated successfully.');
    }

    private function updateUserProfile(User $user, array $data): void
    {
        $user->name = $data['name'] ?? $user->name;
        $user->phone_number = $data['phone_number'] ?? $user->phone_number;
        $user->update();
    }
}
