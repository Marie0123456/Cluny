<?php

namespace App\Http\Controllers;

use App\Models\Concours;

class EngageController extends Controller
{
    public function index(Concours $concours)
    {
        $epreuves = $concours->epreuves()
            ->with(['engagements' => function ($query) {
                $query->withCount('modifications')->with(['cavalier', 'cheval']);
            }])
            ->orderBy('date')
            ->orderByRaw('CAST(numero AS UNSIGNED), numero')
            ->get();

        $epreuvesByDate = $epreuves->groupBy(fn($e) => $e->date ? $e->date->format('Y-m-d') : 'sans_date');

        return view('concours.engages', compact('concours', 'epreuves', 'epreuvesByDate'));
    }
}
