<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    public function index(Request $request, Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $stats = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->selectRaw('COUNT(DISTINCT engagements.cavalier_id) as nb_cavaliers_uniques')
            ->selectRaw('COUNT(DISTINCT cavaliers.club) as nb_clubs')
            ->first();

        // Extract available disciplines from epreuve names (prefix before " - ")
        $disciplines = $concours->epreuves()
            ->pluck('nom')
            ->map(fn ($nom) => trim(explode(' - ', $nom)[0]))
            ->unique()
            ->sort()
            ->values();

        // Multi-epreuve cavaliers filtered by discipline(s)
        $selectedDisciplines = array_filter((array) $request->query('disciplines', []));
        $multiEpreuveCavaliers = collect();

        if (! empty($selectedDisciplines)) {
            $query = DB::table('engagements')
                ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
                ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
                ->where('epreuves.concours_id', $concours->id)
                ->where(function ($q) use ($selectedDisciplines) {
                    foreach ($selectedDisciplines as $disc) {
                        $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                    }
                })
                ->select('cavaliers.id', 'cavaliers.nom', 'cavaliers.prenom', 'cavaliers.club')
                ->selectRaw('COUNT(DISTINCT epreuves.id) as nb_epreuves')
                ->selectRaw("STRING_AGG(DISTINCT epreuves.nom, ', ' ORDER BY epreuves.nom) as epreuves_liste")
                ->groupBy('cavaliers.id', 'cavaliers.nom', 'cavaliers.prenom', 'cavaliers.club')
                ->havingRaw('COUNT(DISTINCT epreuves.id) > 1')
                ->orderBy('cavaliers.nom')
                ->orderBy('cavaliers.prenom');

            $multiEpreuveCavaliers = $query->get();
        }

        return view('concours.statistiques', compact('concours', 'stats', 'disciplines', 'selectedDisciplines', 'multiEpreuveCavaliers'));
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

    public function exportMultiEpreuves(Request $request, Concours $concours)
    {
        $selectedDisciplines = array_filter((array) $request->query('disciplines', []));
        if (empty($selectedDisciplines)) {
            return redirect()->route('concours.statistiques.index', $concours);
        }

        $cavaliers = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->where(function ($q) use ($selectedDisciplines) {
                foreach ($selectedDisciplines as $disc) {
                    $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                }
            })
            ->select('cavaliers.nom', 'cavaliers.prenom', 'cavaliers.club')
            ->selectRaw('COUNT(DISTINCT epreuves.id) as nb_epreuves')
            ->selectRaw("STRING_AGG(DISTINCT epreuves.nom, ', ' ORDER BY epreuves.nom) as epreuves_liste")
            ->groupBy('cavaliers.nom', 'cavaliers.prenom', 'cavaliers.club')
            ->havingRaw('COUNT(DISTINCT epreuves.id) > 1')
            ->orderBy('cavaliers.nom')
            ->orderBy('cavaliers.prenom')
            ->get();

        $discLabel = implode('_', $selectedDisciplines);
        $filename = 'multi_epreuves_' . str_replace(' ', '_', $discLabel) . '_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($cavaliers) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Nom', 'Prenom', 'Club', 'Nb epreuves', 'Epreuves'], ';');

            foreach ($cavaliers as $c) {
                fputcsv($handle, [$c->nom, $c->prenom, $c->club ?? '', $c->nb_epreuves, $c->epreuves_liste], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
