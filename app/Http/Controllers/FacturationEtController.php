<?php

namespace App\Http\Controllers;

use App\Enums\ModificationType;
use App\Models\Concours;

class FacturationEtController extends Controller
{
    public function index(Concours $concours)
    {
        $modifications = $concours->modifications()
            ->whereIn('type', [
                ModificationType::AJOUT_ENGAGEMENT->value,
                ModificationType::CHANGEMENT_EPREUVE->value,
            ])
            ->where('statut', '!=', 'supprime')
            ->with([
                'engagement.epreuve',
                'engagement.cavalier',
                'engagement.cheval',
                'clientFacturation',
            ])
            ->latest()
            ->get();

        $totalPrix = $modifications->sum('prix');
        $totalPf = $modifications->sum('pf');

        return view('concours.facturation-et.index', compact(
            'concours', 'modifications', 'totalPrix', 'totalPf'
        ));
    }
}
