<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DoctorQualification extends Model
{
    protected $guarded = [];

    protected function certificateUrl(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? env('APP_URL', 'https://cura.solvere.website') . '/storage/' . $value : null,
        );
    }


    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
