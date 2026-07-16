<?php

namespace App\Enum;

enum TimeSlotPeriod: string
{
    case HOURLY = 'hourly';
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
}
