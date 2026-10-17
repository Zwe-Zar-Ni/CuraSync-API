<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorScheduleOverrideRequest extends Model
{
    protected $guarded = [];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // ? Admin
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
