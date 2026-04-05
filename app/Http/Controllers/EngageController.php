<?php

namespace App\Http\Controllers;

use App\Models\Concours;

class EngageController extends Controller
{
    public function index(Concours $concours)
    {
        $epreuves = $concours->epreuves()
            ->with(['engagements' => function ($query) {
                $query->withCount('modifications')
                    ->with(['cavalier', 'cheval'])
                    ->orderByRaw("CASE WHEN numero_depart ~ '^[0-9]+$' THEN CAST(numero_depart AS INTEGER) END NULLS LAST, numero_depart NULLS LAST");
            }])
            ->orderBy('date')
            ->orderByRaw('CAST(numero AS INTEGER), numero')
            ->get();

        $epreuvesByDate = $epreuves->groupBy(fn($e) => $e->date ? $e->date->format('Y-m-d') : 'sans_date');

        return view('concours.engages', compact('concours', 'epreuves', 'epreuvesByDate'));
    }
}
