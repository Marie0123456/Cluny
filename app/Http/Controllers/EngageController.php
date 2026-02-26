<?php

namespace App\Http\Controllers;

use App\Models\Concours;

class EngageController extends Controller
{
    public function index(Concours $concours)
    {
        $epreuves = $concours->epreuves()
            ->with(['engagements.cavalier', 'engagements.cheval'])
            ->orderByRaw('CAST(numero AS UNSIGNED), numero')
            ->get();

        return view('concours.engages', compact('concours', 'epreuves'));
    }
}
