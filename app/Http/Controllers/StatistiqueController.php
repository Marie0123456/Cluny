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

    public function exportCavaliers(Concours $concours)
    {
        $cavaliers = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->select('cavaliers.nom', 'cavaliers.prenom', 'cavaliers.club')
            ->distinct()
            ->orderBy('cavaliers.nom')
            ->orderBy('cavaliers.prenom')
            ->get();

        $filename = 'cavaliers_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($cavaliers) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Nom', 'Prenom', 'Club'], ';');

            foreach ($cavaliers as $c) {
                fputcsv($handle, [$c->nom, $c->prenom, $c->club ?? ''], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportClubs(Concours $concours)
    {
        $clubs = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->whereNotNull('cavaliers.club')
            ->where('cavaliers.club', '!=', '')
            ->select('cavaliers.club')
            ->distinct()
            ->orderBy('cavaliers.club')
            ->pluck('club');

        $filename = 'clubs_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($clubs) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Club'], ';');

            foreach ($clubs as $club) {
                fputcsv($handle, [$club], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
