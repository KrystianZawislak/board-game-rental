<?php

namespace App\Enum;

enum ReservationStatus: string
{
    case ISSUED = 'issued';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Oczekująca',
            self::CONFIRMED => 'Potwierdzona',
            self::ISSUED => 'Wydana',
            self::RETURNED => 'Zwrócona',
        };
    }
}

