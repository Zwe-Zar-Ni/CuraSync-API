<?php

namespace App\Models;

use App\Builders\DoctorBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseEloquentBuilder(DoctorBuilder::class)]
class Doctor extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scheduleOverrides(): HasMany
    {
        return $this->hasMany(DoctorScheduleOverride::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(DoctorQualification::class);
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(Specialization::class, 'doctor_specialty');
    }
}
