<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return $this->error(
                ['email' => ['These credentials do not match our records.']],
                'Invalid credentials',
                401
            );
        }

        $user = User::where('email', $credentials['email'])->with('roles')->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'user' => $user,
                'roles' => $user->getRoleNames()->all(),
                'token' => $token,
            ],
            'Logged in successfully'
        );
    }

    public function registerPatient(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $user->assignRole('patient');

        $user->patient()->save(new Patient);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'user' => $user,
                'roles' => $user->getRoleNames()->all(),
                'token' => $token,
            ],
            'Patient registered successfully',
            201
        );
    }

    public function registerDoctor(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $user->assignRole('doctor');

        $doctor = new Doctor(['status' => 'PENDING_VERIFICATION']);
        $user->doctor()->save($doctor);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'user' => $user,
                'roles' => $user->getRoleNames()->all(),
                'doctor' => $doctor,
                'token' => $token,
            ],
            'Doctor registered successfully',
            201
        );
    }
}
