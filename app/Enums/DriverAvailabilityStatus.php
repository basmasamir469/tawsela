<?php

namespace App\Enums;

enum DriverAvailabilityStatus: string
{
    case AVAILABLE = 'available';
    case BUSY = 'busy';
}
