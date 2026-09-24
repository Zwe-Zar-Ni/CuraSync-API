<?php

namespace App\Http\Controllers\V1;

use App\Enums\DoctorStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('auth')->plainTextToken;

            return $this->success(
                [
                    'user' => $user,
                    'roles' => $user->getRoleNames()->all(),
                    'token' => $token,
                ]
            );
        }

        return $this->error(
            ['email' => ['The provided credentials are incorrect.']],
            'Invalid Credentials',
            401
        );
    }

    public function registerPatient(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $user->assignRole('patient');
            $user->patient()->save(new Patient);

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'user' => $user,
                'roles' => $user->getRoleNames()->all(),
                'token' => $token,
            ],
            '',
            201
        );
    }

    public function registerDoctor(RegisterRequest $request): JsonResponse
    {
        $profile = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $user->assignRole('doctor');

            $doctor = new Doctor(['status' => DoctorStatus::PendingVerification->value]);
            $user->doctor()->save($doctor);

            return [
                'user' => $user,
                'doctor' => $doctor,
            ];
        });

        $token = $profile->user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'user' => $profile->user,
                'roles' => $profile->user->getRoleNames()->all(),
                'doctor' => $profile->doctor,
                'token' => $token,
            ],
            '',
            201
        );
    }
}
