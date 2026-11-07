# Auth Migration: Sanctum + Spatie request-time checks -> JWT

## Status

Implemented (2026-11-07): Sanctum was replaced with the JWT guard, refresh-token
rotation, claim-based role middleware, and JWKS endpoints. Google sign-in
endpoints remain deferred (see Future section). This document is the source of
truth for the auth migration; update the task list as work progresses.

## Problem

The current auth stack is Laravel/Sanctum + Spatie request-time role checks.
Two problems:

1. **Cost per request.** Authenticating and authorizing a request touches roughly
   four database lookups:
   - `personal_access_tokens` lookup for the Sanctum token
   - `users` lookup for the token owner
   - Spatie role/pivot lookups by the `role:*` middleware
   - another role lookup in `ProfileResource::getRoleNames()`

2. **Coupling to this Laravel app.** Sanctum uses opaque tokens stored in this
   app's database. A second backend (planned Kotlin/Spring Boot service) cannot
   validate those tokens without calling Laravel or sharing Laravel's database
   and models. Authorization is also expressed in Laravel-specific middleware and
   Spatie models, which do not translate to another runtime.

A third, forward-looking requirement: Google sign-up/sign-in will be added later.
The auth design should accommodate an external identity provider without rework.

## Solution

Move authentication to **stateless, standard JWT access tokens**, signed
asymmetrically, and move authorization to **claims embedded in the token**.

- **Library:** `firebase/php-jwt` with a small custom Laravel guard. It is
  framework-agnostic, so it does not lock the token format to Laravel.
- **Algorithm:** `RS256` (asymmetric). Laravel holds the private key; other
  services only need the public key.
- **Access token:** short-lived JWT (~15 minutes) with `iss`, `aud`, `sub`,
  `jti`, `token_use=access`, `role`, `ver`, `iat`, `nbf`, `exp`.
- **Refresh token:** opaque random token, SHA-256 hashed in the database, rotated
  on every use.
- **Request-time query budget:** decode signature + load `User` by `sub` = **one
  DB query**. Roles come from the token claim, so Spatie is not queried per
  request.
- **Immediate invalidation without extra queries:** `users.token_version` is
  compared with the `ver` claim on the same `User` row already fetched. Bumping
  `token_version` invalidates all existing access tokens for that user.
- **Interoperability:** publish `/.well-known/jwks.json` (and optionally
  `/.well-known/openid-configuration`). Spring Boot validates tokens as a
  standard OAuth2 resource server.
- **Spatie:** kept as the **write-time** source of truth for assigning roles
  (registration, admin actions). It is no longer used for request-time
  authorization.
- **Google:** later, Google's verified ID token is exchanged for the same
  Laravel-issued JWT pair. The token layer does not change.

## Goal

- Authenticate/authorize with 1 DB query instead of ~4.
- Any backend in any language can validate Laravel-issued access tokens using
  standard JWT verification and the public key/JWKS.
- Preserve current API behavior and the `{data, errors, message, status}`
  envelope.
- Keep roles working (`patient`, `doctor`, `admin`) without Spatie per-request
  lookups.
- Be ready for Google sign-in without changing the JWT contract.

## Non-goals

- OAuth2 authorization-server features (client credentials, consent screens,
  third-party apps). This is an auth issuer + resource server setup, not a full
  OAuth2 provider.
- Encrypting token contents (JWE). Claims are signed, not secret; do not put
  sensitive data in them.
- Eliminating DB access from domain endpoints. Patient/doctor profile queries
  still hit the DB; only auth overhead drops.

## Locked decisions

| Decision | Choice |
| --- | --- |
| JWT implementation | Custom guard + `firebase/php-jwt` |
| Signing | RS256, asymmetric keys |
| Query target | 1 query per authenticated request (`User` by `sub`) |
| Role storage | Spatie remains write-time source; roles embedded in token |
| Role authorization | Custom `role.jwt` middleware reading claims |
| Refresh strategy | Opaque token, hashed in DB, rotated on use |
| Token invalidation | `users.token_version` vs `ver` claim |
| Google | Prep now (nullable password, centralized token issuance); endpoints later |
| Migrations | Early stage: edit existing migrations, then `migrate:refresh` |

## Token and claims contract

Access token header:

```json
{
  "alg": "RS256",
  "typ": "at+jwt",
  "kid": "<key-id>"
}
```

Access token payload:

```json
{
  "iss": "https://api.curasync.example",
  "aud": "curasync-api",
  "sub": "42",
  "jti": "2a6f0f7e-...",
  "token_use": "access",
  "role": "doctor",
  "ver": 1,
  "iat": 1762500000,
  "nbf": 1762500000,
  "exp": 1762500900
}
```

Rules:

- `sub` is the **Laravel user ID as a string** per JWT convention. Do not put
  Google's subject here.
- `role` is one of `patient`, `doctor`, `admin`.
- `ver` is compared to `users.token_version`.
- Access tokens are the only JWTs. Refresh tokens are opaque.
- Do not put email, phone, medical data, or other PII in the token unless a
  downstream service truly needs it.

## Request-time query budget

| Concern | Before | After |
| --- | --- | --- |
| Token lookup | 1 (`personal_access_tokens`) | 0 (signature verified locally) |
| User lookup | 1 (`users`) | 1 (`User::retrieveById($sub)`) |
| Role lookup | 1+ (Spatie) | 0 (claim) |
| ProfileResource role | 1 (Spatie) | 0 (claim) |

Domain endpoints may add their own profile queries; that is expected.

## Schema changes (edit existing migrations, no new files)

Early-stage policy: modify the existing migrations and run
`php artisan migrate:refresh --seed`. Do not add alter-table migrations.

Edit `database/migrations/0001_01_01_000000_create_users_table.php`:

- `password` becomes nullable (for future Google-only accounts).
- Add `unsignedInteger('token_version')->default(0)`.
- Add a `refresh_tokens` table in the same auth migration:
  - `id`
  - `foreignId('user_id')->constrained()->cascadeOnDelete()`
  - `string('token_hash', 64)->unique()`
  - `timestamp('expires_at')`
  - `timestamp('revoked_at')->nullable()`
  - `string('user_agent')->nullable()`
  - `string('ip_address', 45)->nullable()`
  - `timestamps()`
- Update `down()` to drop `refresh_tokens` before `users`.

Also remove the Sanctum token table:

- Delete `database/migrations/2026_09_23_131302_create_personal_access_tokens_table.php`
  (or empty it) because Sanctum is being removed in the same change.

Future Google schema (not now): `oauth_accounts` with `user_id`, `provider`,
`provider_user_id`, `email`, unique `(provider, provider_user_id)`.

## Files: new

- `config/jwt.php` - algorithm, key paths, TTLs, issuer, audience, clock skew.
- `app/Auth/JwtGuard.php` - custom guard: decode bearer token, load user, verify
  `token_version`, expose claims.
- `app/Services/JwtService.php` - issue access/refresh pairs, decode/validate,
  rotate refresh tokens, revoke, bump versions.
- `app/Http/Middleware/EnsureJwtRole.php` - claim-based role gate.
- `app/Models/RefreshToken.php` - `$guarded = []`, date casts.
- `app/Http/Controllers/V1/JwksController.php` - JWKS and optional OIDC discovery.
- Tests under `tests/Unit` and `tests/Feature` (directories do not exist yet).

## Files: modified

- `composer.json` - add `firebase/php-jwt`; remove `laravel/sanctum`.
- `.env.example` - add JWT settings.
- `config/auth.php` - add `api` guard (`driver => 'jwt'`).
- `bootstrap/app.php` - register `role.jwt` alias; remove `role` alias if unused.
- `routes/api.php` - `auth:sanctum` -> `auth:api`; `role:*` -> `role.jwt:*`.
- `app/Models/User.php` - remove `HasApiTokens`; add `token_version` support.
- `app/Http/Controllers/V1/AuthController.php` - issue JWT pairs; add refresh and
  logout.
- `app/Http/Resources/ProfileResource.php` - role from claims, not
  `getRoleNames()`.
- `database/migrations/0001_01_01_000000_create_users_table.php` - schema changes.
- `app/Providers/AppServiceProvider.php` (or new `JwtServiceProvider`) - register
  the `jwt` guard driver.
- `AGENTS.md` - update auth/authz conventions after implementation.

## Files: removed

- `config/sanctum.php`
- `database/migrations/2026_09_23_131302_create_personal_access_tokens_table.php`
- `HasApiTokens` trait usage in `app/Models/User.php`

## Implementation phases

### Phase 1 - Dependencies, keys, configuration

- [x] `composer require firebase/php-jwt`
- [x] Generate RS256 keys under `storage/jwt/`:
  - `openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out storage/jwt/private.pem`
  - `openssl rsa -pubout -in storage/jwt/private.pem -out storage/jwt/public.pem`
  - `chmod 600 storage/jwt/private.pem`
- [x] Add `storage/jwt` to `.gitignore`.
- [x] Add `.env` / `.env.example` keys: `JWT_ALGO=RS256`, key paths/content,
  `JWT_TTL=15`, `JWT_REFRESH_TTL=20160`, `JWT_ISSUER`, `JWT_AUDIENCE`,
  `JWT_CLOCK_SKEW`.
- [x] Create `config/jwt.php`.
- [x] Add the `api` guard in `config/auth.php`.

### Phase 2 - Schema

- [x] Edit `create_users_table`: nullable password, `token_version`,
  `refresh_tokens` table, correct `down()` order.
- [x] Delete the Sanctum personal-access-tokens migration.
- [x] Create `app/Models/RefreshToken.php`.
- [x] Run `php artisan migrate:refresh --seed` and confirm roles exist.

### Phase 3 - JWT service and guard

- [x] Implement `JwtService`:
  - issue access token with `kid` header and full claim set
  - issue opaque refresh token and store its SHA-256 hash
  - decode: verify signature/exp/nbf with leeway, then enforce `iss`, `aud`,
    `token_use=access`
  - rotate refresh token on use
  - revoke refresh tokens; bump `token_version`
- [x] Implement `JwtGuard`:
  - read `Authorization: Bearer`
  - decode via `JwtService`
  - load user with the configured provider by `sub`
  - reject if user missing (covers soft deletes) or `ver` mismatch
  - store decoded claims on the request (`jwt.claims`)
- [x] Register the `jwt` guard driver in a service provider.

### Phase 4 - Endpoints, routes, authorization

- [x] Rewrite `AuthController::login` to validate credentials with `Hash::check`
  and issue a token pair.
- [x] Rewrite `registerPatient` / `registerDoctor` to issue a token pair after the
  existing DB transaction + `assignRole`.
- [x] Add `POST /api/v1/auth/refresh` and `POST /api/v1/auth/logout`.
- [x] Add `throttle` to login and refresh.
- [x] Change protected routes from `auth:sanctum` to `auth:api`.
- [x] Add `EnsureJwtRole` and replace `role:*` middleware with `role.jwt:*`.
- [x] Update `ProfileResource` to read role from `jwt.claims` with a non-JWT
  fallback for seeders/tests.
- [x] Keep the response envelope; document the new token fields.

### Phase 5 - Interop

- [x] Add `/.well-known/jwks.json` (`kty`, `use`, `alg`, `kid`, `n`, `e`).
- [x] Optionally add `/.well-known/openid-configuration` so Spring's
  `issuer-uri` discovery works.
- [x] Include `kid` in the JWT header so keys can rotate.
- [x] Write `AUTH_DOCS.md` for the Kotlin/Spring Boot service.
- [x] Cross-verify a Laravel-issued token with the public key in a test.

### Phase 6 - Cleanup and verification

- [x] `composer remove laravel/sanctum`; delete `config/sanctum.php`.
- [x] Remove `HasApiTokens` from `app/Models/User.php`.
- [x] Create `tests/Unit` and `tests/Feature`.
- [x] Add tests: login/register, valid/expired/tampered token, wrong
  issuer/audience, `token_version` mismatch, role gating, refresh rotation/reuse,
  JWKS verification.
- [x] `composer test` (PHPUnit now targets MySQL `curasync_test` because the
  SQLite PDO driver is unavailable locally; create the DB once if needed)
- [x] `vendor/bin/pint`
- [x] Update `AGENTS.md` auth conventions.

## Security checklist

- [x] RS256 only; reject `alg: none` and unexpected algorithms.
- [x] Enforce `iss`, `aud`, `exp`, `nbf`; allow small clock skew.
- [x] Never store tokens or `Authorization` headers in logs.
- [x] Refresh tokens stored only as hashes; rotate and detect reuse.
- [ ] On password change, role change, or status change: bump `token_version`
  and revoke refresh tokens. (Deferred until those mutation endpoints exist;
  `JwtService` already exposes the helpers.)
- [ ] Do not trust client-provided identity; verify Google ID tokens later.
  (Deferred with the Google endpoint.)
- [ ] HTTPS only; keep the private key out of git and out of other services.
  (Private key is gitignored; HTTPS is a deployment concern.)
- [x] Rate-limit login and refresh endpoints.

## Acceptance criteria

- Authenticated requests resolve the user with exactly 1 query for auth.
- `role.jwt:patient`, `role.jwt:doctor`, and `role.jwt:admin` work without
  Spatie queries.
- Expired, tampered, wrong-issuer, wrong-audience, and stale-`ver` tokens are
  rejected with 401.
- Refresh rotation invalidates the old refresh token.
- No `personal_access_tokens` table or Sanctum dependency remains.
- A Spring Boot resource server can validate a Laravel-issued token using only
  the JWKS URL.
- `composer test` and `vendor/bin/pint` pass.

## Future: Google sign-in (additive, after this migration)

- Client obtains a Google ID token via Google Identity Services and posts it to
  `POST /api/v1/auth/google` with a role for new users.
- Backend verifies the ID token against Google's JWKS (`iss`, `aud`, `exp`,
  `nonce`, `email_verified`), then finds/creates the user, assigns the role,
  links `oauth_accounts`, and calls `JwtService::issueTokenPair()`.
- No changes to the JWT claims, guard, middleware, refresh flow, or JWKS.

## Open items / risks

- Google endpoint and `oauth_accounts` table are intentionally deferred.
- If an admin changes a role later, the caller must bump `token_version` or
  revoke refresh tokens; otherwise the old role lives until the access token
  expires (max ~15 minutes).
- Logout of a single device cannot revoke a stateless access token without a
  blacklist lookup. Options: accept expiry, bump `token_version` (all devices),
  or add a JTI blacklist (extra lookup).
- `JWT_ISSUER` must be a stable public URL; changing it invalidates all tokens.
