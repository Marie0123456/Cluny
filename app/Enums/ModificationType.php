<?php

namespace App\Enums;

enum ModificationType: string
{
    case CHANGEMENT_CHEVAL = 'changement_cheval';
    case CHANGEMENT_CAVALIER = 'changement_cavalier';
    case AJOUT_ENGAGEMENT = 'ajout_engagement';
    case CHANGEMENT_EPREUVE = 'changement_epreuve';
}
