# CuraSync Backend

API-only Laravel 13 app (PHP ^8.3, `composer.json` pins `laravel/framework ^13.17`) for the CuraSync telemedicine platform. Early-stage: the profile DB schema is committed, but `routes/api.php` still only has the Sanctum stub — no real endpoints, controllers, or tests exist yet. Domain specs live in the repo root (`../summary.md`, `../user.md`, `../doctor.md`, `../admin.md`, `../condition-statuses.md`).

## Commands

- Setup: `composer install`, copy `.env.example` to `.env`, then `php artisan key:generate`
- Dev server: `composer dev` (runs `php artisan dev`)
- Migrate + seed: `php artisan migrate --seed`
- Tests: `composer test` (or `php artisan test`) — PHPUnit is configured for in-memory SQLite (`phpunit.xml`), so tests never touch the local MySQL DB
- Lint: `vendor/bin/pint` (only code-style tool; no `pint.json`, default Laravel preset)

There is no frontend: no `package.json`/Vite/`node_modules`. The `npm install`/`npm run build` steps in composer's `setup` script are stale skeleton leftovers — ignore them.

## Conventions

- JSON envelope from `BaseController` (`app/Http/Controllers/BaseController.php`): all responses are `{data, errors, message, status}` via `success()`/`error()`. Paginate with `->paginate()` and include `extractPaginationMeta()`. New controllers extend `BaseController`, not the abstract `Controller`.
- Throw `CustomApiException` (`app/Exceptions/CustomApiException.php`) for API errors; `bootstrap/app.php` renders JSON for `api/*` requests.
- Enums are DB `enum` columns with `UPPER_SNAKE` values (doctor status, allergy severity, condition status, schedule-override type). Patient `gender` is `'M'`/`'F'`.
- `doctor_schedules.day_of_week` is enum `0..6` where **0 = Sunday**.
- Doctor↔specialization pivot table is `doctor_specialty` — note the singular name, not the alphabetical `doctor_specialization`.
- Profile models (`Patient`, `Doctor`, `DoctorSchedule*`, `Doctor*`, `Specialization`, `PatientAllergy/Condition/Contact`) are empty shells; use Laravel 13 attribute-based `#[Fillable]`/`#[Hidden]`/`#[Casts]` like `app/Models/User.php` (which uses `#[Fillable]`, `#[Hidden]` instead of `$fillable`/`$hidden`).
- RBAC via `spatie/laravel-permission` (`HasRoles` on `User`): roles Patient, Doctor, Admin; teams disabled (`config/permission.php`).
- New FKs follow the existing pattern: `$table->foreignId('x_id')->constrained()->cascadeOnDelete()`.

## Gotchas

- `.env` targets MySQL (`DB_DATABASE=curasync`) and is gitignored — never commit secrets. For local verification, prefer `php artisan test` (SQLite in-memory); for MySQL work you must create the `curasync` database first.
- `git log` has one commit ("Profiles database schema"); the profile models and `CustomApiException` are currently untracked work.
- GitHub CI and pre-commit hooks are absent; `builder`, `boost`, and other skeleton packages are not installed.