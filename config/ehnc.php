<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TVA par défaut pour les modifications (engagements tardifs)
    |--------------------------------------------------------------------------
    | Taux de TVA appliqué aux calculs HT des modifications.
    | 5.5% => coefficient multiplicateur 1.055
    */
    'tva_modifications' => 5.5,

    /*
    |--------------------------------------------------------------------------
    | Tarification des modifications (FFE Compet standard)
    |--------------------------------------------------------------------------
    */
    'tarifs' => [
        'invitation_supplement' => 15.0,
        'invitation_pf' => 14.40,
        'invitation_supplement_sif' => 10.0,
        'invitation_pf_sif' => 9.90,
        'pf_grand_national' => 4.80,
    ],
];
