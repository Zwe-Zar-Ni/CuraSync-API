<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DoctorQualification extends Model
{
    protected $guarded = [];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Attachment::class, 'id', 'certificate_id');
    }
}
