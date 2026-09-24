# CuraSync Backend

API-only Laravel 13 app (PHP ^8.3, `composer.json` pins `laravel/framework ^13.17`) for the CuraSync telemedicine platform. Auth is in place — `POST /api/v1/auth/login`, `/register/patient`, `/register/doctor` — and patient/doctor domain CRUD endpoints exist under `v1/patients/*` and `v1/doctors/*`. All tests are still unwritten. Domain specs live at the repo root (`../summary.md`, `../user.md`, `../doctor.md`, `../admin.md`, `../condition-statuses.md`).

## Commands

- Setup: `composer install`, copy `.env.example` to `.env`, then `php artisan key:generate`
- Dev server: `composer dev` (runs `php artisan dev`)
- Migrate + seed: `php artisan migrate --seed` (seeds `SpecializationSeeder` — 10 specializations)
- `php artisan db:seed --class=SpecializationSeeder` (idempotent via `firstOrCreate`)
- Roles: `php artisan roles:create` (patient/doctor/admin) and `php artisan create:admin` (admin@example.com / password) are custom Artisan commands in `routes/console.php`. **`DatabaseSeeder` does not create roles — run `roles:create` or registration `assignRole()` calls will throw.**
- Tests: `composer test` — PHPUnit is configured for in-memory SQLite (`phpunit.xml`), but **currently errors** because `tests/Unit` and `tests/Feature` don't exist yet; create them first.
- Lint: `vendor/bin/pint` (only code-style tool; no `pint.json`, default Laravel preset)

There is no frontend: no `package.json`/Vite/`node_modules`. The `npm install`/`npm run build` steps in composer's `setup` script are stale skeleton leftovers — ignore them.

## Conventions

- JSON envelope from `BaseController` (`app/Http/Controllers/BaseController.php`): all responses are `{data, errors, message, status}` via `success()`/`error()`. Paginate with `->paginate()` and include `extractPaginationMeta()`. New controllers extend `BaseController`, not the abstract `Controller`.
- Throw `CustomApiException` (`app/Exceptions/CustomApiException.php`) for API errors; `bootstrap/app.php` renders JSON for `api/*` requests and appends `ForceJsonRequestHeader` (forces `Accept: application/json`) globally.
- Auth endpoints live in `routes/api.php` under a `v1` prefix group (`App\Http\Controllers\V1\*`). `registerPatient`/`registerDoctor` create the User + assign role + create the profile inside a `DB::transaction`; `registerDoctor` defaults `Doctor.status` to `PENDING_VERIFICATION`.
- Domain CRUD controllers extend `BaseController` and live under `App\Http\Controllers\V1\Patient\*` and `App\Http\Controllers\V1\Doctor\*`; each has a private `getPatientId()`/`getDoctorId()` helper returning `$request->user()->patient?->id ?? 0` (doctor variant same), scopes every query to the authenticated user's profile, and uses the `create([...])`-with-explicit-keys form.
- CRUD resources under the `patients` group (`role:patient`): `allergies` (AllergyController), `conditions` (ConditionController), `contacts` (ContactController). Under the `doctors` group (`role:doctor`): `qualifications` (QualificationController), `schedules` (ScheduleController), `schedule-overrides` (ScheduleOverrideController); `specialties` is create+delete only (DoctorSpecialtyController, pivot `doctor_specialty`).
- Eloquent requests live next to controllers as `App\Http\Requests\Patient\{Resource}\{Store,Update}{Resource}Request` / `App\Http\Requests\Doctor\{Resource}\{Store,Update}{Resource}Request`; validation uses `Rule::enum(...)`, `'date_format:H:i'`, `'after:start_time'`, `Rule::in([...])` for `day_of_week`, and `required_if:type,CUSTOM_HOURS` for override times. Update rules currently mirror store rules (both required).
- `User` (only `User`) uses Laravel 13 attribute-based `#[Fillable]`/`#[Hidden]`; all profile models (`Patient`, `Doctor`, `Doctor*`, `Specialization`, `PatientAllergy/Condition/Contact`) use `protected $guarded = []` instead.
- Enums are DB `enum` columns with `UPPER_SNAKE` values: doctor status `ACTIVE`/`PENDING_VERIFICATION`/`INACTIVE`/`SUSPENDED` (default `PENDING_VERIFICATION`), condition status `ACTIVE`/`INACTIVE`/`RESOLVED`/`REMISSION`/`RECURRENCE`/`CONFIRMED`/`PROVISIONAL`/`REFUTED`, allergy severity `MILD`/`MODERATE`/`SEVERE`, schedule-override type `UNAVAILABLE`/`CUSTOM_HOURS`. Patient `gender` is `'M'`/`'F'`.
- `doctor_schedules.day_of_week` is enum `'0'..'6'` where **0 = Sunday**.
- Doctor↔specialization pivot table is `doctor_specialty` — note the singular name, not the alphabetical `doctor_specialization`.
- RBAC via `spatie/laravel-permission` (`HasRoles` on `User`): roles Patient, Doctor, Admin; teams disabled (`config/permission.php`).
- New FKs follow the existing pattern: `$table->foreignId('x_id')->constrained()->cascadeOnDelete()`.

## Gotchas

- `.env` targets MySQL (`DB_DATABASE=curasync`) and is gitignored — never commit secrets. For local verification, prefer in-memory SQLite (`php artisan test`); for MySQL work you must create the `curasync` database first. A stray gitignored `database/database.sqlite` exists from the skeleton.
- `git log` has three commits (schema → models → auth); the auth changes in `app/Exceptions/CustomApiException.php`, `app/Http/Controllers/V1/AuthController.php`, and `routes/api.php` are uncommitted work, as are the patient/doctor domain CRUD controllers, form requests, seeders, and their `routes/api.php` additions.
- GitHub CI and pre-commit hooks are absent; `builder`, `boost`, and other skeleton packages are not installed (`CLAUDE.md` still contains the stale boost-install instructions — ignore it).
