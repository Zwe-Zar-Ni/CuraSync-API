<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class JwtService
{
    public function issueTokenPair(User $user): array
    {
        $access = $this->issueAccessToken($user);
        $refresh = $this->issueRefreshToken($user);

        return [
            'access_token' => $access['token'],
            'token_type' => 'Bearer',
            'expires_in' => $access['expires_in'],
            'refresh_token' => $refresh['token'],
            'refresh_expires_in' => $refresh['expires_in'],
        ];
    }

    public function issueAccessToken(User $user): array
    {
        $now = time();
        $expiresIn = $this->accessTokenTtlSeconds();
        $expiresAt = $now + $expiresIn;

        $claims = [
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $user->getKey(),
            'jti' => (string) Str::uuid(),
            'token_use' => 'access',
            'role' => $this->roleFor($user),
            'ver' => (int) $user->token_version,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expiresAt,
        ];

        $token = JWT::encode(
            $claims,
            $this->privateKey(),
            $this->algorithm(),
            $this->keyId(),
            ['typ' => 'at+jwt'],
        );

        return [
            'token' => $token,
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt,
        ];
    }

    public function issueRefreshToken(User $user): array
    {
        $plain = bin2hex(random_bytes(32));
        $expiresIn = $this->refreshTtlSeconds();
        $request = request();

        RefreshToken::create([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addSeconds($expiresIn),
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
        ]);

        return [
            'token' => $plain,
            'expires_in' => $expiresIn,
        ];
    }

    public function decodeAccessToken(string $token): object
    {
        JWT::$leeway = (int) config('jwt.clock_skew', 30);

        try {
            $claims = JWT::decode($token, new Key($this->publicKey(), $this->algorithm()));
        } catch (Throwable $e) {
            throw new AuthenticationException('Unauthenticated.', [], $e);
        }

        if (($claims->iss ?? null) !== config('jwt.issuer')) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $audience = $claims->aud ?? null;
        $audience = is_array($audience) ? $audience : [$audience];

        if (! in_array(config('jwt.audience'), $audience, true)) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (($claims->token_use ?? null) !== 'access') {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $claims;
    }

    public function refresh(string $plainRefreshToken): array
    {
        $record = RefreshToken::where('token_hash', hash('sha256', $plainRefreshToken))->first();

        if (! $record) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $user = $record->user;

        if (! $user || $user->trashed()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if ($record->revoked_at !== null) {
            $this->revokeAllRefreshTokens($user);
            $this->bumpTokenVersion($user);

            throw new AuthenticationException('Unauthenticated.');
        }

        if ($record->expires_at->isPast()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $record->update(['revoked_at' => now()]);

        return $this->issueTokenPair($user);
    }

    public function revokeRefreshToken(string $plainRefreshToken, ?User $user = null): void
    {
        RefreshToken::where('token_hash', hash('sha256', $plainRefreshToken))
            ->when($user, fn ($query) => $query->where('user_id', $user->getKey()))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllRefreshTokens(User $user): void
    {
        RefreshToken::where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function bumpTokenVersion(User $user): void
    {
        $user->increment('token_version');
        $user->refresh();
    }

    public function jwks(): array
    {
        $key = openssl_pkey_get_public($this->publicKey());

        if ($key === false) {
            throw new RuntimeException('Unable to read the JWT public key.');
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || ! isset($details['rsa'])) {
            throw new RuntimeException('The JWT public key is not an RSA key.');
        }

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => $this->algorithm(),
                    'kid' => $this->keyId(),
                    'n' => $this->base64Url($details['rsa']['n']),
                    'e' => $this->base64Url($details['rsa']['e']),
                ],
            ],
        ];
    }

    public function keyId(): string
    {
        return substr(hash('sha256', $this->publicKey()), 0, 16);
    }

    private function roleFor(User $user): ?string
    {
        return $user->getRoleNames()->first();
    }

    private function algorithm(): string
    {
        return (string) config('jwt.algo', 'RS256');
    }

    private function accessTokenTtlSeconds(): int
    {
        return max(1, (int) config('jwt.ttl', 15)) * 60;
    }

    private function refreshTtlSeconds(): int
    {
        return max(1, (int) config('jwt.refresh_ttl', 20160)) * 60;
    }

    private function privateKey(): string
    {
        return $this->keyMaterial(config('jwt.private_key'), config('jwt.private_key_path'));
    }

    private function publicKey(): string
    {
        return $this->keyMaterial(config('jwt.public_key'), config('jwt.public_key_path'));
    }

    private function keyMaterial(?string $inline, string $path): string
    {
        if (is_string($inline) && trim($inline) !== '') {
            return $inline;
        }

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            throw new RuntimeException("JWT key not found at [{$path}].");
        }

        return file_get_contents($path);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
