<?php

namespace App\Enums;

enum ConditionStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Resolved = 'RESOLVED';
    case Remission = 'REMISSION';
    case Recurrence = 'RECURRENCE';
    case Confirmed = 'CONFIRMED';
    case Provisional = 'PROVISIONAL';
    case Refuted = 'REFUTED';
}
