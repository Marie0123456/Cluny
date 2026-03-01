<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    public function index(Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $stats = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->selectRaw('COUNT(DISTINCT engagements.cavalier_id) as nb_cavaliers_uniques')
            ->selectRaw('COUNT(DISTINCT cavaliers.club) as nb_clubs')
            ->first();

        return view('concours.statistiques', compact('concours', 'stats'));
    }
}
