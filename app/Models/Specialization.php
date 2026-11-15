<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Specialization extends Model
{
    protected $guarded = [];

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_specialty');
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? env('APP_URL', 'https://cura.solvere.website') . '/storage/' . $value : null,
        );
    }
}
