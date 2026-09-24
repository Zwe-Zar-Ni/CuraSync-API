<?php

namespace App\Enums;

enum ScheduleOverrideType: string
{
    case Unavailable = 'UNAVAILABLE';
    case CustomHours = 'CUSTOM_HOURS';
}