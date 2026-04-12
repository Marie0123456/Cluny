<?php

namespace App\Enums;

enum ModificationType: string
{
    case CHANGEMENT_CHEVAL = 'changement_cheval';
    case CHANGEMENT_CAVALIER = 'changement_cavalier';
    case AJOUT_ENGAGEMENT = 'ajout_engagement';
    case CHANGEMENT_EPREUVE = 'changement_epreuve';
    case NON_PARTANT = 'non_partant';

    public function label(): string
    {
        return match ($this) {
            self::CHANGEMENT_CHEVAL => 'Changement de cheval',
            self::CHANGEMENT_CAVALIER => 'Changement de cavalier',
            self::AJOUT_ENGAGEMENT => 'Invitation',
            self::CHANGEMENT_EPREUVE => "Changement d'épreuve",
            self::NON_PARTANT => 'Non-partant',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CHANGEMENT_CHEVAL => 'bg-orange-100 text-orange-800',
            self::CHANGEMENT_CAVALIER => 'bg-purple-100 text-purple-800',
            self::AJOUT_ENGAGEMENT => 'bg-blue-100 text-blue-800',
            self::CHANGEMENT_EPREUVE => 'bg-yellow-100 text-yellow-800',
            self::NON_PARTANT => 'bg-gray-100 text-gray-800',
        };
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::AJOUT_ENGAGEMENT, self::CHANGEMENT_EPREUVE]);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::AJOUT_ENGAGEMENT, self::CHANGEMENT_EPREUVE, self::CHANGEMENT_CHEVAL]);
    }

    public function hasCheval(): bool
    {
        return in_array($this, [self::AJOUT_ENGAGEMENT, self::CHANGEMENT_CHEVAL]);
    }
}
