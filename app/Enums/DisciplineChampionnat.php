<?php

namespace App\Enums;

enum DisciplineChampionnat: string
{
    case CSO = 'CSO';
    case HUNTER = 'Hunter';
    case DRESSAGE = 'Dressage';

    public function usesPercentage(): bool
    {
        return match ($this) {
            self::HUNTER, self::DRESSAGE => true,
            self::CSO => false,
        };
    }

    public function higherIsBetter(): bool
    {
        return match ($this) {
            self::HUNTER, self::DRESSAGE => true,
            self::CSO => false,
        };
    }

    public function hasTwoEpreuves(): bool
    {
        return match ($this) {
            self::CSO, self::HUNTER => true,
            self::DRESSAGE => false,
        };
    }
}
