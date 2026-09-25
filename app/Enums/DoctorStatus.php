<?php

namespace App\Enums;

enum DoctorStatus: string
{
    case Active = 'ACTIVE';
    case PendingVerification = 'PENDING_VERIFICATION';
    case Inactive = 'INACTIVE';
    case Suspended = 'SUSPENDED';
}
