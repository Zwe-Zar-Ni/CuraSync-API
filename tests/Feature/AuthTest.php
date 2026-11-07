<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Database\Seeders\RoleSeeder;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_patient_can_register_and_receive_tokens(): void
    {
        $response = $this->postJson('/api/v1/auth/register/patient', $this->patientPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('data.role', 'patient')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'role',
                    'access_token',
                    'token_type',
                    'expires_in',
                    'refresh_token',
                ],
                'errors',
                'message',
                'status',
            ]);
    }

    public function test_user_can_login_and_access_protected_route(): void
    {
        $payload = $this->patientPayload();
        $this->postJson('/api/v1/auth/register/patient', $payload)->assertCreated();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $login->assertOk()->assertJsonPath('data.role', 'patient');

        $this->withToken($login->json('data.access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'patient');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJsonPath('status', 401);
    }

    public function test_patient_cannot_access_doctor_routes(): void
    {
        $data = $this->registerPatientAndGetTokens();

        $this->withToken($data['access_token'])
            ->getJson('/api/v1/doctors/qualifications')
            ->assertForbidden()
            ->assertJsonPath('status', 403);
    }

    public function test_doctor_can_access_doctor_routes(): void
    {
        $data = $this->registerDoctorAndGetTokens();

        $this->withToken($data['access_token'])
            ->getJson('/api/v1/doctors/qualifications')
            ->assertOk();
    }

    public function test_expired_token_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $user = User::where('email', $data['email'])->firstOrFail();

        $token = $this->tokenFor($user, [
            'iat' => time() - 600,
            'nbf' => time() - 600,
            'exp' => time() - 300,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('status', 401);
    }

    public function test_token_with_wrong_audience_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $user = User::where('email', $data['email'])->firstOrFail();

        $token = $this->tokenFor($user, ['aud' => 'another-api']);

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_token_with_wrong_issuer_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $user = User::where('email', $data['email'])->firstOrFail();

        $token = $this->tokenFor($user, ['iss' => 'https://other.example']);

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_tampered_token_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $token = $data['access_token'];
        $tampered = substr($token, 0, -1).(str_ends_with($token, 'A') ? 'B' : 'A');

        $this->withToken($tampered)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_token_version_mismatch_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $user = User::where('email', $data['email'])->firstOrFail();
        $user->increment('token_version');

        $this->withToken($data['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_refresh_token_rotates_and_old_token_is_rejected(): void
    {
        $data = $this->registerPatientAndGetTokens();

        $refresh = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $data['refresh_token'],
        ]);

        $refresh
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in', 'refresh_token'],
            ]);

        $this->withToken($refresh->json('data.access_token'))
            ->getJson('/api/v1/me')
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $data['refresh_token'],
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $data = $this->registerPatientAndGetTokens();

        $this->withToken($data['access_token'])
            ->postJson('/api/v1/auth/logout', ['refresh_token' => $data['refresh_token']])
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $data['refresh_token'],
        ])->assertUnauthorized();
    }

    public function test_jwks_endpoint_exposes_the_signing_key(): void
    {
        $response = $this->getJson('/.well-known/jwks.json');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'keys' => [['kty', 'use', 'alg', 'kid', 'n', 'e']],
            ]);

        $this->assertSame('RSA', $response->json('keys.0.kty'));
        $this->assertSame(app(JwtService::class)->keyId(), $response->json('keys.0.kid'));
    }

    public function test_access_token_can_be_verified_with_the_published_jwks(): void
    {
        $data = $this->registerPatientAndGetTokens();
        $jwks = $this->getJson('/.well-known/jwks.json')->json();

        $keys = JWK::parseKeySet($jwks, 'RS256');
        $decoded = JWT::decode($data['access_token'], $keys);

        $this->assertSame('access', $decoded->token_use);
        $this->assertSame('patient', $decoded->role);
    }

    public function test_openid_configuration_points_to_the_jwks_endpoint(): void
    {
        $issuer = rtrim((string) config('jwt.issuer'), '/');

        $this->getJson('/.well-known/openid-configuration')
            ->assertOk()
            ->assertJsonPath('issuer', $issuer)
            ->assertJsonPath('jwks_uri', $issuer.'/.well-known/jwks.json');
    }

    private function registerPatientAndGetTokens(): array
    {
        $payload = $this->patientPayload();
        $response = $this->postJson('/api/v1/auth/register/patient', $payload)->assertCreated();

        return [
            ...$response->json('data'),
            'email' => $payload['email'],
        ];
    }

    private function registerDoctorAndGetTokens(): array
    {
        $payload = $this->doctorPayload();
        $response = $this->postJson('/api/v1/auth/register/doctor', $payload);

        $response->assertOk();

        return [
            ...$response->json('data'),
            'email' => $payload['email'],
        ];
    }

    private function patientPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Patient One',
            'email' => 'patient-'.Str::uuid().'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function doctorPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Doctor One',
            'email' => 'doctor-'.Str::uuid().'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function tokenFor(User $user, array $overrides = []): string
    {
        $now = time();
        $claims = array_merge([
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $user->getKey(),
            'jti' => (string) Str::uuid(),
            'token_use' => 'access',
            'role' => $user->getRoleNames()->first(),
            'ver' => (int) $user->token_version,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
        ], $overrides);

        $path = config('jwt.private_key_path');
        $path = str_starts_with($path, '/') ? $path : base_path($path);

        return JWT::encode(
            $claims,
            file_get_contents($path),
            config('jwt.algo'),
            app(JwtService::class)->keyId(),
            ['typ' => 'at+jwt'],
        );
    }
}
