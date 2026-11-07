<?php

namespace App\Http\Controllers\V1;

use App\Enums\DoctorStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function __construct(private readonly JwtService $jwt) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error(
                ['email' => ['The provided credentials are incorrect.']],
                'Invalid Credentials',
                401
            );
        }

        return $this->success($this->authPayload($user, $request), 'Logged in successfully.');
    }

    public function registerPatient(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $user->assignRole('patient');
            $user->patient()->save(new Patient);

            return $user;
        });

        return $this->success($this->authPayload($user, $request), '', 201);
    }

    public function registerDoctor(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $user->assignRole('doctor');
            $doctor = new Doctor(['status' => DoctorStatus::PendingVerification->value]);
            $user->doctor()->save($doctor);

            return $user;
        });

        return $this->success($this->authPayload($user, $request), '');
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            $tokens = $this->jwt->refresh($validated['refresh_token']);
        } catch (AuthenticationException) {
            return $this->error(
                ['refresh_token' => ['The refresh token is invalid or expired.']],
                'Unauthenticated',
                401
            );
        }

        return $this->success($tokens, 'Token refreshed successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['nullable', 'string'],
        ]);

        if (! empty($validated['refresh_token'])) {
            $this->jwt->revokeRefreshToken($validated['refresh_token'], $request->user());
        } else {
            $this->jwt->revokeAllRefreshTokens($request->user());
        }

        return $this->success([], 'Logged out successfully.');
    }

    private function authPayload(User $user, Request $request): array
    {
        $user->loadMissing('roles');

        return [
            ...(new ProfileResource($user))->toArray($request),
            ...$this->jwt->issueTokenPair($user),
        ];
    }
}
