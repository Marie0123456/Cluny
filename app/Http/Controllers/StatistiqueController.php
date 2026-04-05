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
        $multiEpreuveNoms = collect();

        if (! empty($selectedDisciplines)) {
            $rows = DB::table('engagements')
                ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
                ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
                ->where('epreuves.concours_id', $concours->id)
                ->where(function ($q) use ($selectedDisciplines) {
                    foreach ($selectedDisciplines as $disc) {
                        $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                    }
                })
                ->select(
                    'cavaliers.id as cavalier_id',
                    'cavaliers.nom',
                    'cavaliers.prenom',
                    'cavaliers.club',
                    'epreuves.nom as epreuve_nom',
                    'engagements.numero_depart'
                )
                ->orderBy('epreuves.nom')
                ->get();

            // Collect all unique epreuve names (ordered)
            $multiEpreuveNoms = $rows->pluck('epreuve_nom')->unique()->sort()->values();

            // Group by cavalier and keep only those with > 1 epreuve
            $multiEpreuveCavaliers = $rows->groupBy('cavalier_id')
                ->filter(fn ($group) => $group->pluck('epreuve_nom')->unique()->count() > 1)
                ->map(function ($group) {
                    $first = $group->first();
                    $epreuves = $group->map(fn ($r) => [
                        'nom' => $r->epreuve_nom,
                        'numero_depart' => $r->numero_depart,
                    ])->sortBy('nom')->values();

                    return (object) [
                        'nom' => $first->nom,
                        'prenom' => $first->prenom,
                        'club' => $first->club,
                        'nb_epreuves' => $epreuves->pluck('nom')->unique()->count(),
                        'epreuves' => $epreuves,
                        'epreuves_liste' => $epreuves->map(fn ($e) => $e['nom'] . ($e['numero_depart'] ? ' (N°' . $e['numero_depart'] . ')' : ''))->implode(', '),
                    ];
                })
                ->sortBy(fn ($c) => $c->nom . ' ' . $c->prenom)
                ->values();
        }

        // Combinaisons d'epreuves les plus frequentes
        $selectedDisciplinesCombinaisons = array_filter((array) $request->query('disciplines_combinaisons', []));
        $combinaisonsGroupBy = $request->query('combinaisons_group_by', 'cavalier');
        if (! in_array($combinaisonsGroupBy, ['cavalier', 'cheval'])) {
            $combinaisonsGroupBy = 'cavalier';
        }
        $combinaisons = collect();

        if (! empty($selectedDisciplinesCombinaisons)) {
            $combiQuery = DB::table('engagements')
                ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
                ->where('epreuves.concours_id', $concours->id)
                ->where(function ($q) use ($selectedDisciplinesCombinaisons) {
                    foreach ($selectedDisciplinesCombinaisons as $disc) {
                        $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                    }
                });

            if ($combinaisonsGroupBy === 'cheval') {
                $combiQuery->join('chevaux', 'engagements.cheval_id', '=', 'chevaux.id');
                $rowsCombi = $combiQuery
                    ->select('chevaux.id as group_id', 'chevaux.nom as group_nom', DB::raw("'' as group_prenom"), 'epreuves.nom as epreuve_nom')
                    ->orderBy('epreuves.nom')
                    ->get();
            } else {
                $combiQuery->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id');
                $rowsCombi = $combiQuery
                    ->select('cavaliers.id as group_id', 'cavaliers.nom as group_nom', 'cavaliers.prenom as group_prenom', 'epreuves.nom as epreuve_nom')
                    ->orderBy('epreuves.nom')
                    ->get();
            }

            $combinaisons = $rowsCombi->groupBy('group_id')
                ->filter(fn ($group) => $group->pluck('epreuve_nom')->unique()->count() > 1)
                ->map(fn ($group) => $group->pluck('epreuve_nom')->unique()->sort()->values()->all())
                // When multiple disciplines selected, keep only combinations covering all disciplines
                ->when(count($selectedDisciplinesCombinaisons) > 1, function ($collection) use ($selectedDisciplinesCombinaisons) {
                    return $collection->filter(function ($epreuves) use ($selectedDisciplinesCombinaisons) {
                        foreach ($selectedDisciplinesCombinaisons as $disc) {
                            $discLower = mb_strtolower($disc);
                            $found = false;
                            foreach ($epreuves as $ep) {
                                if (str_starts_with(mb_strtolower($ep), $discLower)) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (! $found) {
                                return false;
                            }
                        }
                        return true;
                    });
                })
                ->groupBy(fn ($epreuves) => implode(' + ', $epreuves))
                ->map(function ($groups, $combiKey) use ($rowsCombi, $combinaisonsGroupBy) {
                    $groupIds = $groups->keys();
                    $names = $rowsCombi->whereIn('group_id', $groupIds)
                        ->unique('group_id')
                        ->map(fn ($r) => $combinaisonsGroupBy === 'cheval'
                            ? $r->group_nom
                            : trim($r->group_prenom . ' ' . $r->group_nom))
                        ->sort()
                        ->values()
                        ->all();

                    return (object) [
                        'epreuves' => explode(' + ', $combiKey),
                        'label' => $combiKey,
                        'count' => $groups->count(),
                        'entities' => $names,
                    ];
                })
                ->sortByDesc('count')
                ->values();
        }

        // Multi-epreuve chevaux filtered by discipline(s)
        $selectedDisciplinesChevaux = array_filter((array) $request->query('disciplines_chevaux', []));
        $multiEpreuveChevaux = collect();
        $multiEpreuveNomsChevaux = collect();

        if (! empty($selectedDisciplinesChevaux)) {
            $rowsChevaux = DB::table('engagements')
                ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
                ->join('chevaux', 'engagements.cheval_id', '=', 'chevaux.id')
                ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
                ->where('epreuves.concours_id', $concours->id)
                ->where(function ($q) use ($selectedDisciplinesChevaux) {
                    foreach ($selectedDisciplinesChevaux as $disc) {
                        $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                    }
                })
                ->select(
                    'chevaux.id as cheval_id',
                    'chevaux.nom as cheval_nom',
                    'chevaux.num_sire',
                    'cavaliers.nom as cavalier_nom',
                    'cavaliers.prenom as cavalier_prenom',
                    'epreuves.nom as epreuve_nom',
                    'engagements.numero_depart'
                )
                ->orderBy('epreuves.nom')
                ->get();

            $multiEpreuveNomsChevaux = $rowsChevaux->pluck('epreuve_nom')->unique()->sort()->values();

            $multiEpreuveChevaux = $rowsChevaux->groupBy('cheval_id')
                ->filter(fn ($group) => $group->pluck('epreuve_nom')->unique()->count() > 1)
                ->map(function ($group) {
                    $first = $group->first();
                    $epreuves = $group->map(fn ($r) => [
                        'nom' => $r->epreuve_nom,
                        'numero_depart' => $r->numero_depart,
                        'cavalier' => $r->cavalier_nom . ' ' . $r->cavalier_prenom,
                    ])->sortBy('nom')->values();

                    return (object) [
                        'cheval_nom' => $first->cheval_nom,
                        'num_sire' => $first->num_sire,
                        'nb_epreuves' => $epreuves->pluck('nom')->unique()->count(),
                        'epreuves' => $epreuves,
                    ];
                })
                ->sortBy(fn ($c) => $c->cheval_nom)
                ->values();
        }

        return view('concours.statistiques', compact(
            'concours', 'stats', 'disciplines', 'selectedDisciplines', 'multiEpreuveCavaliers', 'multiEpreuveNoms',
            'selectedDisciplinesCombinaisons', 'combinaisons', 'combinaisonsGroupBy',
            'selectedDisciplinesChevaux', 'multiEpreuveChevaux', 'multiEpreuveNomsChevaux'
        ));
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

        $rows = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->where(function ($q) use ($selectedDisciplines) {
                foreach ($selectedDisciplines as $disc) {
                    $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                }
            })
            ->select(
                'cavaliers.id as cavalier_id',
                'cavaliers.nom',
                'cavaliers.prenom',
                'cavaliers.club',
                'epreuves.nom as epreuve_nom',
                'engagements.numero_depart'
            )
            ->orderBy('epreuves.nom')
            ->get();

        $epreuveNoms = $rows->pluck('epreuve_nom')->unique()->sort()->values();

        $cavaliers = $rows->groupBy('cavalier_id')
            ->filter(fn ($group) => $group->pluck('epreuve_nom')->unique()->count() > 1)
            ->map(function ($group) use ($epreuveNoms) {
                $first = $group->first();
                $epreuveMap = $group->keyBy('epreuve_nom');

                $columns = $epreuveNoms->map(fn ($epNom) => isset($epreuveMap[$epNom]) ? ('N°' . ($epreuveMap[$epNom]->numero_depart ?? '-')) : '');

                return [
                    'nom' => $first->nom,
                    'prenom' => $first->prenom,
                    'club' => $first->club ?? '',
                    'nb_epreuves' => $group->pluck('epreuve_nom')->unique()->count(),
                    'epreuve_columns' => $columns->all(),
                ];
            })
            ->sortBy(fn ($c) => $c['nom'] . ' ' . $c['prenom'])
            ->values();

        $discLabel = implode('_', $selectedDisciplines);
        $filename = 'multi_epreuves_' . str_replace(' ', '_', $discLabel) . '_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($cavaliers, $epreuveNoms) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $header = array_merge(['Nom', 'Prenom', 'Club', 'Nb epreuves'], $epreuveNoms->all());
            fputcsv($handle, $header, ';');

            foreach ($cavaliers as $c) {
                $row = array_merge([$c['nom'], $c['prenom'], $c['club'], $c['nb_epreuves']], $c['epreuve_columns']);
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportMultiEpreuvesChevaux(Request $request, Concours $concours)
    {
        $selectedDisciplines = array_filter((array) $request->query('disciplines_chevaux', []));
        if (empty($selectedDisciplines)) {
            return redirect()->route('concours.statistiques.index', $concours);
        }

        $rows = DB::table('engagements')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->join('chevaux', 'engagements.cheval_id', '=', 'chevaux.id')
            ->join('cavaliers', 'engagements.cavalier_id', '=', 'cavaliers.id')
            ->where('epreuves.concours_id', $concours->id)
            ->where(function ($q) use ($selectedDisciplines) {
                foreach ($selectedDisciplines as $disc) {
                    $q->orWhereRaw('LOWER(epreuves.nom) LIKE ?', [mb_strtolower($disc) . '%']);
                }
            })
            ->select(
                'chevaux.id as cheval_id',
                'chevaux.nom as cheval_nom',
                'chevaux.num_sire',
                'cavaliers.nom as cavalier_nom',
                'cavaliers.prenom as cavalier_prenom',
                'epreuves.nom as epreuve_nom',
                'engagements.numero_depart'
            )
            ->orderBy('epreuves.nom')
            ->get();

        $epreuveNoms = $rows->pluck('epreuve_nom')->unique()->sort()->values();

        $chevaux = $rows->groupBy('cheval_id')
            ->filter(fn ($group) => $group->pluck('epreuve_nom')->unique()->count() > 1)
            ->map(function ($group) use ($epreuveNoms) {
                $first = $group->first();
                $epreuveMap = $group->keyBy('epreuve_nom');

                $columns = $epreuveNoms->map(fn ($epNom) => isset($epreuveMap[$epNom]) ? ('N°' . ($epreuveMap[$epNom]->numero_depart ?? '-')) : '');

                return [
                    'cheval_nom' => $first->cheval_nom,
                    'num_sire' => $first->num_sire ?? '',
                    'nb_epreuves' => $group->pluck('epreuve_nom')->unique()->count(),
                    'epreuve_columns' => $columns->all(),
                ];
            })
            ->sortBy(fn ($c) => $c['cheval_nom'])
            ->values();

        $discLabel = implode('_', $selectedDisciplines);
        $filename = 'multi_epreuves_chevaux_' . str_replace(' ', '_', $discLabel) . '_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($chevaux, $epreuveNoms) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $header = array_merge(['Cheval', 'SIRE', 'Nb epreuves'], $epreuveNoms->all());
            fputcsv($handle, $header, ';');

            foreach ($chevaux as $c) {
                $row = array_merge([$c['cheval_nom'], $c['num_sire'], $c['nb_epreuves']], $c['epreuve_columns']);
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
