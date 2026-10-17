<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorScheduleRequest extends Model
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

    public function doctorSchedule()
    {
        return $this->belongsTo(DoctorSchedule::class);
    }
}
