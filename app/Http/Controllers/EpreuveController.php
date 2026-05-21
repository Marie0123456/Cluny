<?php

namespace App\Http\Controllers;

use App\Models\Concours;

class EpreuveController extends Controller
{
    public function index(Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $epreuves = $concours->epreuves()
            ->withCount([
                'engagements',
                'engagements as invitations_count' => function ($query) {
                    $query->where('is_invitation', true);
                },
                'engagements as non_partants_count' => function ($query) {
                    $query->where('is_non_partant', true);
                },
            ])
            ->orderByRaw("CASE WHEN numero ~ '^[0-9]+$' THEN CAST(numero AS INTEGER) END NULLS LAST, numero")
            ->get();

        return view('concours.epreuves', compact('concours', 'epreuves'));
    }
}
