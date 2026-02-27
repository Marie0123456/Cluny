<?php

namespace App\Enums;

enum ModificationStatut: string
{
    case CREE = 'cree';
    case FAIT = 'fait';
    case MODIFIE = 'modifie';
    case SUPPRIME = 'supprime';
}
