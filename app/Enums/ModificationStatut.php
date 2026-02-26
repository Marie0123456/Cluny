<?php

namespace App\Enums;

enum ModificationStatut: string
{
    case EN_ATTENTE = 'en_attente';
    case FAIT = 'fait';
    case SUPPRIME = 'supprime';
}
