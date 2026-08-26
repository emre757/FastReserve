<?php

namespace App\Enums;

enum OfferingStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
