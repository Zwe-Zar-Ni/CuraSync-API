<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case CheckedIn = 'CHECKED_IN';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case NoShow = 'NO_SHOW';
}
