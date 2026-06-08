<?php 

namespace App\Enum;

enum GameCategory: string
{
    case STRATEGY = 'strategy';
    case FAMILY   = 'family';
    case PARTY    = 'party';
    case CARD     = 'card';
    case COOP     = 'coop';
    case ECONOMIC = 'economic';

    public function label(): string
    {
        return match ($this) {
            self::STRATEGY => 'Strategiczne',
            self::FAMILY   => 'Rodzinne',
            self::PARTY    => 'Imprezowe',
            self::CARD     => 'Karciane',
            self::COOP     => 'Kooperacyjne',
            self::ECONOMIC => 'Ekonomiczne',
        };
    }
}

