<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Attachment extends Model
{
    protected $guarded = [];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? env('APP_URL', 'https://cura.solvere.website') . '/storage/' . $value : null,
        );
    }
}
