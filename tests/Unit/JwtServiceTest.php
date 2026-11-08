<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\JwtService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_token_contains_the_expected_claims(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('doctor');
        $user->refresh();

        $service = app(JwtService::class);
        $issued = $service->issueAccessToken($user);
        $claims = $service->decodeAccessToken($issued['token']);

        $this->assertSame((string) $user->id, $claims->sub);
        $this->assertSame('doctor', $claims->role);
        $this->assertSame('access', $claims->token_use);
        $this->assertSame($user->token_version, $claims->ver);
    }

    public function test_jwks_contains_the_rsa_signing_key(): void
    {
        $jwks = app(JwtService::class)->jwks();

        $this->assertSame('RSA', $jwks['keys'][0]['kty']);
        $this->assertSame('sig', $jwks['keys'][0]['use']);
        $this->assertSame('RS256', $jwks['keys'][0]['alg']);
        $this->assertSame(app(JwtService::class)->keyId(), $jwks['keys'][0]['kid']);
    }
}
