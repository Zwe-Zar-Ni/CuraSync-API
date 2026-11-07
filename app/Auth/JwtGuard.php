<?php

namespace App\Auth;

use App\Services\JwtService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class JwtGuard implements Guard
{
    use GuardHelpers;

    protected bool $resolved = false;

    protected ?object $claims = null;

    public function __construct(
        UserProvider $provider,
        protected JwtService $jwt,
        protected Request $request,
    ) {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;

        $token = $this->request->bearerToken();

        if (! $token) {
            return null;
        }

        try {
            $claims = $this->jwt->decodeAccessToken($token);
        } catch (AuthenticationException) {
            return null;
        }

        $user = $this->provider->retrieveById((int) $claims->sub);

        if (! $user) {
            return null;
        }

        if ((int) ($user->token_version ?? 0) !== (int) ($claims->ver ?? -1)) {
            return null;
        }

        $this->claims = $claims;
        $this->request->attributes->set('jwt.claims', $claims);
        $this->request->attributes->set('jwt.role', $claims->role ?? null);

        return $this->user = $user;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function payload(): ?object
    {
        $this->user();

        return $this->claims;
    }

    public function hasPayload(): bool
    {
        return $this->claims !== null;
    }
}
