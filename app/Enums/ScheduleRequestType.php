<?php

namespace App\Enums;

enum ScheduleRequestType: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
}
