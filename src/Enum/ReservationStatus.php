<?php

namespace App\Enum;

enum ReservationStatus: string 
{
    case ISSUED = 'issued';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case RETURNED = 'returned';
}

