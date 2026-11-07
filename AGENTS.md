# CuraSync Backend

API-only Laravel 13 app (PHP ^8.3, `composer.json` pins `laravel/framework ^13.17`) for the CuraSync telemedicine platform. Auth is JWT-based — `POST /api/v1/auth/login`, `/register/patient`, `/register/doctor`, `/refresh`, `/logout` — and patient/doctor domain CRUD endpoints exist under `v1/patients/*` and `v1/doctors/*`. Domain specs live at the repo root (`../summary.md`, `../user.md`, `../doctor.md`, `../admin.md`, `../condition-statuses.md`). Auth design and cross-service contract live in `AUTH_MIGRATION.md` and `AUTH_DOCS.md`.

## Commands

- Setup: `composer install`, copy `.env.example` to `.env`, then `php artisan key:generate`
- JWT keys: generate `storage/jwt/private.pem` + `public.pem` with `openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out storage/jwt/private.pem` and `openssl rsa -pubout -in storage/jwt/private.pem -out storage/jwt/public.pem`. `storage/jwt` is gitignored; tests auto-generate a 2048-bit pair when missing.
- Dev server: `composer dev` (runs `php artisan dev`)
- Schema changes: early stage — **edit existing migrations instead of adding alter-table migrations**, then `php artisan migrate:refresh --seed`.
- Migrate + seed: `php artisan migrate --seed` (seeds roles, 10 specializations, sample doctors)
- `php artisan db:seed --class=SpecializationSeeder` (idempotent via `firstOrCreate`)
- Roles: `DatabaseSeeder` runs `RoleSeeder` (patient/doctor/admin). `php artisan roles:create` and `php artisan create:admin` are custom commands in `routes/console.php` for manual use.
- Tests: `composer test` uses MySQL database `curasync_test` (`phpunit.xml`); create it once with `mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS curasync_test"`. `tests/Unit` and `tests/Feature` now exist.
- Lint: `vendor/bin/pint` (only code-style tool; no `pint.json`, default Laravel preset)

There is no frontend: no `package.json`/Vite/`node_modules`. The `npm install`/`npm run build` steps in composer's `setup` script are stale skeleton leftovers — ignore them.

## Conventions

- JSON envelope from `BaseController` (`app/Http/Controllers/BaseController.php`): all responses are `{data, errors, message, status}` via `success()`/`error()`. Paginate with `->paginate()` and include `extractPaginationMeta()`. New controllers extend `BaseController`, not the abstract `Controller`.
- Auth endpoints live in `routes/api.php` under a `v1` prefix group (`App\Http\Controllers\V1\*`). `registerPatient`/`registerDoctor` create the User + assign role + create the profile inside a `DB::transaction`; `registerDoctor` defaults `Doctor.status` to `PENDING_VERIFICATION`. All three auth entry points return an access/refresh token pair via `JwtService::issueTokenPair()`.
- JWT auth: `auth:api` uses the custom `App\Auth\JwtGuard` (RS256, `firebase/php-jwt`). Access tokens are ~15 min JWTs (`sub`, `role`, `ver`, `token_use=access`, `iss`, `aud`, `jti`); refresh tokens are opaque, SHA-256 hashed in `refresh_tokens`, and rotated. The guard loads the user by `sub` (1 query) and rejects stale `ver` values against `users.token_version`. Claims are attached as request attributes `jwt.claims` / `jwt.role`.
- Authorization is claim-based: use `role.jwt:admin|patient|doctor` (`App\Http\Middleware\EnsureJwtRole`), not Spatie's `role` middleware. `ProfileResource` reads the role from `jwt.role`, avoiding a per-request Spatie query.
- JWKS/OIDC endpoints are public in `routes/web.php`: `/.well-known/jwks.json` and `/.well-known/openid-configuration`; config lives in `config/jwt.php`. Spring Boot consumers follow `AUTH_DOCS.md`.
- Spatie is still installed but only as the write-time role source (`assignRole` at registration/admin time). If a role/status/password changes, bump `users.token_version` and revoke refresh tokens so existing access tokens stop working.
- Domain CRUD controllers extend `BaseController` and live under `App\Http\Controllers\V1\Patient\*` and `App\Http\Controllers\V1\Doctor\*`; each has a private `getPatientId()`/`getDoctorId()` helper returning `$request->user()->patient?->id ?? 0` (doctor variant same), scopes every query to the authenticated user's profile, and uses the `create([...])`-with-explicit-keys form.
- CRUD resources under the `patients` group (`role.jwt:patient`): `allergies` (AllergyController), `conditions` (ConditionController), `contacts` (ContactController). Under the `doctors` group (`role.jwt:doctor`): `qualifications` (QualificationController), `schedules` (ScheduleController), `schedule-overrides` (ScheduleOverrideController); `specialties` is create+delete only (DoctorSpecialtyController, pivot `doctor_specialty`).
- Eloquent requests live next to controllers as `App\Http\Requests\Patient\{Resource}\{Store,Update}{Resource}Request` / `App\Http\Requests\Doctor\{Resource}\{Store,Update}{Resource}Request`; validation uses `Rule::enum(...)`, `'date_format:H:i'`, `'after:start_time'`, `Rule::in([...])` for `day_of_week`, and `required_if:type,CUSTOM_HOURS` for override times. Update rules currently mirror store rules (both required).
- `User` (only `User`) uses Laravel 13 attribute-based `#[Fillable]`/`#[Hidden]`; all profile models (`Patient`, `Doctor`, `Doctor*`, `Specialization`, `PatientAllergy/Condition/Contact`) use `protected $guarded = []` instead.
- Enums are DB `enum` columns with `UPPER_SNAKE` values: doctor status `ACTIVE`/`PENDING_VERIFICATION`/`INACTIVE`/`SUSPENDED` (default `PENDING_VERIFICATION`), condition status `ACTIVE`/`INACTIVE`/`RESOLVED`/`REMISSION`/`RECURRENCE`/`CONFIRMED`/`PROVISIONAL`/`REFUTED`, allergy severity `MILD`/`MODERATE`/`SEVERE`, schedule-override type `UNAVAILABLE`/`CUSTOM_HOURS`. Patient `gender` is `'M'`/`'F'`.
- `doctor_schedules.day_of_week` is enum `'0'..'6'` where **0 = Sunday**.
- Doctor↔specialization pivot table is `doctor_specialty` — note the singular name, not the alphabetical `doctor_specialization`.
- RBAC storage via `spatie/laravel-permission` (`HasRoles` on `User`): roles Patient, Doctor, Admin; teams disabled (`config/permission.php`). Request-time authorization uses JWT claims, not Spatie queries.
- Google sign-in is planned but not implemented. `users.password` is nullable and token issuance is centralized in `JwtService` so a future `POST /auth/google` can reuse the same token pair.
- New FKs follow the existing pattern: `$table->foreignId('x_id')->constrained()->cascadeOnDelete()`.
