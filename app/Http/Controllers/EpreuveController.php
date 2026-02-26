<?php

namespace App\Http\Controllers;

use App\Models\Concours;

class EpreuveController extends Controller
{
    public function index(Concours $concours)
    {
        $epreuves = $concours->epreuves()
            ->withCount('engagements')
            ->orderByRaw('CAST(numero AS UNSIGNED), numero')
            ->get();

        return view('concours.epreuves', compact('concours', 'epreuves'));
    }
}
