# File / Attachment Module Plan

Status: planned, not implemented.

Chosen approach: one generic polymorphic module, public disk, owner-scoped allowlist, existing URL fields (`users.profile_url`, `doctor_qualifications.certificate_url`) retained for external links.

## 1. Data Model

New table `attachments`:

```php
Schema::create('attachments', function (Blueprint $table) {
    $table->id();
    $table->morphs('attachable');                       // attachable_type + attachable_id
    $table->foreignId('uploaded_by')->nullable()
          ->constrained('users')->nullOnDelete();
    $table->string('disk', 50)->default('public');
    $table->string('path');                            // relative storage path
    $table->string('original_name');
    $table->string('mime_type', 100);
    $table->string('extension', 20);
    $table->unsignedBigInteger('size');                // bytes
    $table->string('checksum', 64)->nullable();        // sha256
    $table->string('collection')->nullable()->index(); // avatar, certificate, condition-image...
    $table->json('metadata')->nullable();              // width/height etc.
    $table->timestamps();

    $table->unique(['disk', 'path']);
    $table->index(['uploaded_by', 'created_at']);
});
```

Use `Attachment`, not `File`, to avoid confusion with `Illuminate\Support\Facades\File`.

- `app/Models/Attachment.php`: `$guarded = []`, `attachable(): MorphTo`, `uploadedBy(): BelongsTo`.
- `app/Models/Concerns/HasAttachments.php`: `attachments(): MorphMany`.
- Add `use HasAttachments` to `Patient`, `Doctor`, `DoctorQualification`, and `PatientCondition` initially.

## 2. Allowlisted Morph Map

Add `app/Enums/AttachableType.php`:

```php
enum AttachableType: string
{
    case Patient = 'patient';
    case Doctor = 'doctor';
    case Qualification = 'qualification';
    case Condition = 'condition';
}
```

Register aliases in `AppServiceProvider::boot()` via `Relation::enforceMorphMap([...])`.

This prevents clients from submitting arbitrary class names. The same enum also defines ownership:

| Alias | Model | Owner check |
|---|---|---|
| `patient` | `Patient` | `$user->patient?->id === $parent->id` |
| `doctor` | `Doctor` | `$user->doctor?->id === $parent->id` |
| `qualification` | `DoctorQualification` | `$parent->doctor_id === $user->doctor?->id` |
| `condition` | `PatientCondition` | `$parent->patient_id === $user->patient?->id` |

Put ownership resolution in one place, e.g. `app/Services/AttachmentAccessService.php`, so the controller stays small. Return `404` when the parent does not exist and `403` when it belongs to someone else.

## 3. API

Inside the existing `auth:api` group in `routes/api.php`:

```php
Route::get('/files', [FileController::class, 'index']);
Route::post('/files', [FileController::class, 'store']);
Route::get('/files/{id}', [FileController::class, 'show']);
Route::delete('/files/{id}', [FileController::class, 'destroy']);
```

`POST /api/v1/files` multipart fields:

- `file` — required upload
- `attachable_type` — required, one of the enum aliases
- `attachable_id` — required integer
- `collection` — optional label such as `avatar` or `certificate`

`GET /api/v1/files?attachable_type=qualification&attachable_id=5` lists files for an owned parent. `GET /files/{id}` and `DELETE /files/{id}` also enforce ownership. Because the disk is public, the resource can return a direct URL; no signed-download endpoint is needed.

`app/Http/Resources/AttachmentResource.php` should return only safe fields: `id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `extension`, `size`, `collection`, `url`, `uploaded_by`, `created_at`. Do not expose `disk` or `path`.

## 4. Validation and Storage

Create `app/Http/Requests/File/StoreFileRequest.php`:

```php
'file' => [
    'required', 'file',
    'max:10240',
    'mimes:jpg,jpeg,png,webp,pdf',
    'extensions:jpg,jpeg,png,webp,pdf',
],
'attachable_type' => ['required', Rule::enum(AttachableType::class)],
'attachable_id' => ['required', 'integer', 'min:1'],
'collection' => ['nullable', 'string', 'max:50'],
```

Add `config/attachments.php` for `disk`, `max_size_kb`, and allowed extensions/mime types so limits are configurable.

On upload:

1. Resolve and authorize the parent via `AttachmentAccessService`.
2. Store with `$file->store("attachments/{$type}/".date('Y/m'), 'public')` — Laravel generates a random hashed filename.
3. Save the safely basename'd original name, detected MIME (`$file->getMimeType()`), extension, size, and `hash_file('sha256', ...)`.
4. Return `AttachmentResource` with the `Storage::disk('public')->url($path)` URL.

Run `php artisan storage:link` once. Also ensure PHP/nginx upload limits are at least the chosen max size.

## 5. Deletion and Cleanup

- In `Attachment`, register a `deleting` hook that calls `Storage::disk($this->disk)->delete($this->path)`.
- In `HasAttachments::bootHasAttachments()`, register a parent `deleting` hook that deletes its attachment records so files are removed before DB cascade.
- Optionally add a later `attachments:prune-orphans` command for bulk-deleted parents and files missing from disk.

## 6. Tests

Add `tests/Feature/AttachmentTest.php` using `RefreshDatabase`, `Storage::fake('public')`, and the existing registration helpers:

- patient uploads PDF/image to own `Patient` and `PatientCondition`
- doctor uploads image to own `DoctorQualification`
- cross-user upload/view/delete is rejected
- invalid `attachable_type` is rejected
- disallowed MIME/extension is rejected
- oversized file is rejected
- listing returns only files for the owned parent
- delete removes both the DB row and the stored file

## 7. Important Caveat

With the public disk, anyone who knows a file URL can access it, even after its database row is deleted unless the physical file is removed. That is acceptable only if medical reports and prescriptions are not stored here. If sensitive documents enter scope later, add a `visibility` column and a private-disk signed-download route without changing the module's API shape.

## Implementation Order

1. Migration, `Attachment`, `HasAttachments`, add trait to models.
2. `AttachableType`, morph map, `config/attachments.php`.
3. `AttachmentAccessService`.
4. Form requests, resource, controller, routes.
5. Delete hooks and cleanup.
6. Feature tests, then `vendor/bin/pint`.
