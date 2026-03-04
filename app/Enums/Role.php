<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case CHRONOMETREUR = 'chronometreur';
    case VENDEUR = 'vendeur';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::CHRONOMETREUR => 'Chronométreur',
            self::VENDEUR => 'Vendeur',
        };
    }
}
