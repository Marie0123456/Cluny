<?php

namespace App\Enums;

enum DisciplineChampionnat: string
{
    case CSO = 'CSO';
    case HUNTER = 'Hunter';
    case DRESSAGE = 'Dressage';
    case EQUIFEEL = 'Equifeel';
    case EQUIFUN = 'Equifun';
    case ENDURANCE = 'Endurance';

    public function usesPercentage(): bool
    {
        return match ($this) {
            self::HUNTER, self::DRESSAGE => true,
            self::CSO, self::EQUIFEEL, self::EQUIFUN, self::ENDURANCE => false,
        };
    }

    public function higherIsBetter(): bool
    {
        return match ($this) {
            self::HUNTER, self::DRESSAGE => true,
            self::CSO, self::EQUIFEEL, self::EQUIFUN, self::ENDURANCE => false,
        };
    }

    public function hasTwoEpreuves(): bool
    {
        return match ($this) {
            self::CSO, self::HUNTER => true,
            self::DRESSAGE, self::EQUIFEEL, self::EQUIFUN, self::ENDURANCE => false,
        };
    }

    /**
     * Manual ranking: the classement is typed by the user, not computed from points/temps.
     */
    public function isManualRanking(): bool
    {
        return match ($this) {
            self::EQUIFEEL, self::EQUIFUN, self::ENDURANCE => true,
            self::CSO, self::HUNTER, self::DRESSAGE => false,
        };
    }

    /**
     * Only available for concours with discipline = Open.
     */
    public function requiresOpenConcours(): bool
    {
        return $this->isManualRanking();
    }

    /**
     * Keywords to match epreuve names for this discipline (lowercase, no accents).
     * Empty array = no filter (any epreuve is acceptable).
     */
    public function epreuveNameKeywords(): array
    {
        return match ($this) {
            self::EQUIFEEL => ['equifeel'],
            self::EQUIFUN => ['equifun'],
            self::ENDURANCE => ['endurance', 'endurence'],
            default => [],
        };
    }
}
