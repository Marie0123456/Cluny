<?php

namespace App\Http\Controllers;

use App\Enums\Discipline;
use App\Enums\DisciplineChampionnat;
use App\Models\Championnat;
use App\Models\ChampionnatExclusion;
use App\Models\ChampionnatResultat;
use App\Models\Concours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChampionnatController extends Controller
{
    public function index(Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $championnats = $concours->championnats()->with(['epreuve1', 'epreuve2'])->get();

        $epreuves = $concours->epreuves()
            ->orderByRaw('CAST(numero AS INTEGER), numero')
            ->get();

        return view('concours.championnats.index', compact('concours', 'championnats', 'epreuves'));
    }

    public function store(Request $request, Concours $concours)
    {
        $disc = DisciplineChampionnat::tryFrom($request->input('discipline'));
        $needsE2 = $disc && $disc->hasTwoEpreuves();

        // Equifeel/Equifun/Endurance: uniquement pour concours Open
        $isOpen = $concours->discipline === Discipline::OPEN;
        $allowedDisciplines = $isOpen
            ? 'CSO,Hunter,Dressage,Equifeel,Equifun,Endurance'
            : 'CSO,Hunter,Dressage';

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'discipline' => 'required|in:' . $allowedDisciplines,
            'epreuve1_id' => 'required|exists:epreuves,id',
            'epreuve2_id' => [
                $needsE2 ? 'required' : 'nullable',
                'exists:epreuves,id',
                'different:epreuve1_id',
            ],
        ]);

        // Une seule epreuve pour Dressage/Equifeel/Equifun/Endurance
        if (!$needsE2) {
            $validated['epreuve2_id'] = null;
        }

        $concours->championnats()->create($validated);

        return redirect()->route('concours.championnats.index', $concours)
            ->with('success', 'Championnat créé avec succès.');
    }

    public function show(Concours $concours, Championnat $championnat)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $championnat->load(['epreuve1', 'epreuve2']);
        $participants = $championnat->participants();

        $exclusionKeys = $championnat->exclusions
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id)
            ->flip();

        // Classement manuel (Equifeel/Equifun/Endurance): un seul flow special
        if ($championnat->discipline->isManualRanking()) {
            $classementManuel = $this->calculerClassementManuel($championnat, $participants, $exclusionKeys);

            return view('concours.championnats.show', [
                'concours' => $concours,
                'championnat' => $championnat,
                'participants' => $participants,
                'exclusionKeys' => $exclusionKeys,
                'classementManuel' => $classementManuel,
                'resultatsEpreuve1' => collect(),
                'resultatsEpreuve2' => collect(),
                'classementGeneral' => collect(),
            ]);
        }

        // Load all resultats in a single query, then split by epreuve
        $allResultats = $championnat->resultats()
            ->with(['cavalier', 'cheval'])
            ->get();

        $sortEpreuve = $championnat->discipline->higherIsBetter()
            ? [['points', 'desc']]
            : [['points', 'asc'], ['temps', 'asc']];

        $resultatsEpreuve1 = $allResultats
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->sortBy($sortEpreuve)
            ->values();

        $resultatsEpreuve2 = collect();
        if ($championnat->epreuve2_id) {
            $resultatsEpreuve2 = $allResultats
                ->where('epreuve_id', $championnat->epreuve2_id)
                ->sortBy($sortEpreuve)
                ->values();
        }

        // Calculate classement general (pass pre-loaded resultats to avoid duplicate queries)
        $classementGeneral = collect();
        if ($championnat->epreuve2_id) {
            if ($resultatsEpreuve1->isNotEmpty() && $resultatsEpreuve2->isNotEmpty()) {
                $classementGeneral = $this->calculerClassement($championnat, $exclusionKeys, $allResultats);
            }
        } else {
            if ($resultatsEpreuve1->isNotEmpty()) {
                $classementGeneral = $this->calculerClassementSimple($championnat, $exclusionKeys, $allResultats);
            }
        }

        return view('concours.championnats.show', compact(
            'concours', 'championnat', 'participants', 'exclusionKeys',
            'resultatsEpreuve1', 'resultatsEpreuve2', 'classementGeneral'
        ));
    }

    /**
     * Classement manuel: pour Equifeel/Equifun/Endurance, la position est saisie
     * directement par l'utilisateur. 0 = non classe (NP/elimine/hors region).
     */
    private function calculerClassementManuel(Championnat $championnat, $participants, $exclusionKeys): \Illuminate\Support\Collection
    {
        // Charger les resultats existants (position manuelle) pour ce championnat
        $resultats = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->get()
            ->keyBy(fn ($r) => $r->cavalier_id . '-' . $r->cheval_id);

        $classement = $participants->map(function ($p) use ($resultats, $exclusionKeys) {
            $key = $p->cavalier_id . '-' . $p->cheval_id;
            $r = $resultats->get($key);
            return [
                'cavalier_id' => $p->cavalier_id,
                'cheval_id' => $p->cheval_id,
                'cavalier_nom' => $p->cavalier_nom,
                'cavalier_prenom' => $p->cavalier_prenom,
                'cheval_nom' => $p->cheval_nom,
                'club' => $p->club,
                'position' => $r?->position ?? 0,
                'is_excluded' => $exclusionKeys->has($key),
            ];
        });

        // Tri: positions classees (>=1) d'abord par ordre croissant, puis non-classees (0) a la fin
        return $classement->sortBy([
            fn ($a, $b) => ($a['position'] > 0 ? 0 : 1) <=> ($b['position'] > 0 ? 0 : 1),
            fn ($a, $b) => $a['position'] <=> $b['position'],
            fn ($a, $b) => strcasecmp($a['cavalier_nom'] ?? '', $b['cavalier_nom'] ?? ''),
        ])->values();
    }

    /**
     * Vue imprimable (speaker) du classement general du championnat.
     * L'utilisateur imprime ou enregistre en PDF via le navigateur.
     */
    public function printClassement(Concours $concours, Championnat $championnat)
    {
        $championnat->load(['epreuve1', 'epreuve2']);

        $exclusionKeys = $championnat->exclusions
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id)
            ->flip();

        if ($championnat->discipline->isManualRanking()) {
            $participants = $championnat->participants();
            $classement = $this->calculerClassementManuel($championnat, $participants, $exclusionKeys)
                ->filter(fn ($e) => !$e['is_excluded'] && $e['position'] > 0)
                ->map(fn ($e) => [
                    'rang' => $e['position'],
                    'cavalier_prenom' => $e['cavalier_prenom'],
                    'cavalier_nom' => $e['cavalier_nom'],
                    'cheval_nom' => $e['cheval_nom'],
                    'club' => $e['club'],
                ])
                ->values();
        } else {
            $allResultats = $championnat->resultats()->with(['cavalier', 'cheval'])->get();
            $raw = $championnat->epreuve2_id
                ? $this->calculerClassement($championnat, $exclusionKeys, $allResultats)
                : $this->calculerClassementSimple($championnat, $exclusionKeys, $allResultats);

            $classement = $raw
                ->filter(fn ($e) => !($e['is_excluded'] ?? false))
                ->values()
                ->map(fn ($e, $i) => [
                    'rang' => $i + 1,
                    'cavalier_prenom' => $e['cavalier_prenom'],
                    'cavalier_nom' => $e['cavalier_nom'],
                    'cheval_nom' => $e['cheval_nom'],
                    'club' => $e['club'],
                ]);
        }

        return view('concours.championnats.print-classement', compact(
            'concours', 'championnat', 'classement'
        ));
    }

    /**
     * Enregistre la position manuelle d'un couple (Equifeel/Equifun/Endurance).
     */
    public function updatePosition(Request $request, Concours $concours, Championnat $championnat)
    {
        if (!$championnat->discipline->isManualRanking()) {
            return response()->json(['error' => 'Ce championnat n\'utilise pas de classement manuel.'], 422);
        }

        $validated = $request->validate([
            'cavalier_id' => 'required|integer|exists:cavaliers,id',
            'cheval_id' => 'required|integer|exists:chevaux,id',
            'position' => 'required|integer|min:0|max:999',
        ]);

        ChampionnatResultat::updateOrCreate(
            [
                'championnat_id' => $championnat->id,
                'epreuve_id' => $championnat->epreuve1_id,
                'cavalier_id' => $validated['cavalier_id'],
                'cheval_id' => $validated['cheval_id'],
            ],
            [
                'position' => $validated['position'],
                'points' => 0,
                'statut' => $validated['position'] > 0 ? 'normal' : 'non_partant',
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Position enregistrée.');
    }

    public function importResultats(Request $request, Concours $concours, Championnat $championnat)
    {
        $allowedEpreuves = $championnat->epreuve2_id ? '1,2' : '1';
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'epreuve' => "required|in:$allowedEpreuves",
        ]);

        $epreuveId = $request->input('epreuve') == '1'
            ? $championnat->epreuve1_id
            : $championnat->epreuve2_id;

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());

        // Detect encoding and convert to UTF-8
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", $content);

        // Detect separator from header
        $headerLine = trim($lines[0] ?? '');
        $separator = $this->detectCsvSeparator($headerLine);

        // Detect column positions from header
        $headerCols = array_map(fn ($c) => trim($c), str_getcsv($headerLine, $separator));
        $colMap = $this->detectResultColumns($headerCols);

        // Minimum columns required = highest detected index + 1
        $minCols = max($colMap['classement'], $colMap['cheval'], $colMap['cavalier'], $colMap['points']) + 1;

        // Build lookup of engagements for this epreuve: "CHEVAL_NOM_NORMALIZED|CAVALIER_NOM_NORMALIZED" => [cavalier_id, cheval_id]
        $engagements = DB::table('engagements')
            ->join('cavaliers', 'cavaliers.id', '=', 'engagements.cavalier_id')
            ->join('chevaux', 'chevaux.id', '=', 'engagements.cheval_id')
            ->where('engagements.epreuve_id', $epreuveId)
            ->select('cavaliers.id as cavalier_id', 'cavaliers.nom as cavalier_nom', 'cavaliers.prenom as cavalier_prenom',
                     'chevaux.id as cheval_id', 'chevaux.nom as cheval_nom')
            ->get();

        $lookup = [];
        foreach ($engagements as $eng) {
            $key = $this->normalizeForMatch($eng->cheval_nom) . '|' . $this->normalizeForMatch($eng->cavalier_nom . ' ' . $eng->cavalier_prenom);
            $lookup[$key] = ['cavalier_id' => $eng->cavalier_id, 'cheval_id' => $eng->cheval_id];
            // Also index with prenom nom order
            $key2 = $this->normalizeForMatch($eng->cheval_nom) . '|' . $this->normalizeForMatch($eng->cavalier_prenom . ' ' . $eng->cavalier_nom);
            $lookup[$key2] = ['cavalier_id' => $eng->cavalier_id, 'cheval_id' => $eng->cheval_id];
        }

        // Delete existing resultats for this epreuve in this championnat
        $championnat->resultats()->where('epreuve_id', $epreuveId)->delete();

        $imported = 0;
        $skipped = [];

        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            if ($line === '') continue;

            $cols = str_getcsv($line, $separator);

            // Skip header row
            if ($lineIndex === 0) continue;
            if (count($cols) < $minCols) continue;

            $classement = trim($cols[$colMap['classement']] ?? '');
            if ($classement === '' || !is_numeric($classement)) continue;

            $chevalNom = trim($cols[$colMap['cheval']] ?? '');
            $cavalierNom = trim($cols[$colMap['cavalier']] ?? '');
            $pointsRaw = trim($cols[$colMap['points']] ?? '');
            $tempsRaw = $colMap['temps'] !== null ? trim($cols[$colMap['temps']] ?? '') : '';

            if ($chevalNom === '' || $cavalierNom === '') continue;

            // Parse points and statut
            // CSO: penalty = 50 (high points = bad); Hunter/Dressage: penalty = 0 (low % = bad)
            $penaltyValue = $championnat->discipline->higherIsBetter() ? 0 : 50;
            $statut = 'normal';
            $points = 0;
            $temps = null;

            $pointsLower = mb_strtolower($pointsRaw);
            if (str_contains($pointsLower, 'elimin') || str_contains($pointsLower, 'limin')) {
                $statut = 'elimine';
                $points = $penaltyValue;
            } elseif (str_contains($pointsLower, 'non') && str_contains($pointsLower, 'partant')) {
                $statut = 'non_partant';
                $points = $penaltyValue;
            } elseif (str_contains($pointsLower, 'abandon')) {
                $statut = 'abandon';
                $points = $penaltyValue;
            } else {
                $pointsClean = str_replace(['%', ' '], '', $pointsRaw);
                $points = (float) str_replace(',', '.', $pointsClean);
            }

            // Parse temps: "/ 56,11" -> 56.11
            if ($tempsRaw !== '' && $statut === 'normal') {
                $tempsClean = str_replace(['/', ' '], '', $tempsRaw);
                $tempsClean = str_replace(',', '.', $tempsClean);
                $temps = (float) $tempsClean;
            }

            // Match couple
            $matchKey = $this->normalizeForMatch($chevalNom) . '|' . $this->normalizeForMatch($cavalierNom);
            if (!isset($lookup[$matchKey])) {
                $skipped[] = "$cavalierNom / $chevalNom";
                continue;
            }

            $match = $lookup[$matchKey];

            ChampionnatResultat::create([
                'championnat_id' => $championnat->id,
                'epreuve_id' => $epreuveId,
                'cavalier_id' => $match['cavalier_id'],
                'cheval_id' => $match['cheval_id'],
                'points' => $points,
                'temps' => $temps,
                'statut' => $statut,
            ]);

            $imported++;
        }

        $message = "$imported resultats importes.";
        if (!empty($skipped)) {
            $message .= ' ' . count($skipped) . ' couple(s) du CSV non trouves dans les engages de l\'epreuve (nom different ou non inscrit) : ' . implode(', ', $skipped);
        }

        return redirect()->route('concours.championnats.show', [$concours, $championnat])
            ->with('success', $message);
    }

    private function detectCsvSeparator(string $line): string
    {
        $tabCount = substr_count($line, "\t");
        $semicolonCount = substr_count($line, ';');
        $commaCount = substr_count($line, ',');

        if ($tabCount >= $semicolonCount && $tabCount >= $commaCount && $tabCount > 0) {
            return "\t";
        }
        if ($semicolonCount >= $commaCount) {
            return ';';
        }

        return ',';
    }

    private function detectResultColumns(array $headerCols): array
    {
        $map = ['classement' => null, 'cheval' => null, 'cavalier' => null, 'points' => null, 'temps' => null];

        foreach ($headerCols as $i => $col) {
            $normalized = mb_strtolower(trim($col));
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
            $clean = preg_replace('/[^a-z0-9%]/', '', $ascii);

            if ($map['classement'] === null && preg_match('/^(cl|classement|place|rang)$/', $clean)) {
                $map['classement'] = $i;
            } elseif ($map['cheval'] === null && str_contains($clean, 'cheval')) {
                $map['cheval'] = $i;
            } elseif ($map['cavalier'] === null && str_contains($clean, 'cavalier')) {
                $map['cavalier'] = $i;
            } elseif ($map['points'] === null && preg_match('/(point|pts|pen|%|pourcentage|note|score)/', $clean)) {
                $map['points'] = $i;
            } elseif ($map['temps'] === null && preg_match('/(temps|tps|time|chrono|barrage)/', $clean)) {
                $map['temps'] = $i;
            }
        }

        // Second pass: if cavalier not found, look for standalone "nom"
        if ($map['cavalier'] === null) {
            foreach ($headerCols as $i => $col) {
                $clean = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($col)));
                if ($i !== $map['cheval'] && ($clean === 'nom' || $clean === 'nomprenom')) {
                    $map['cavalier'] = $i;
                    break;
                }
            }
        }

        // Fallback to hardcoded positions if header detection failed
        if ($map['classement'] === null) $map['classement'] = 0;
        if ($map['cheval'] === null) $map['cheval'] = 2;
        if ($map['cavalier'] === null) $map['cavalier'] = 5;
        if ($map['points'] === null) $map['points'] = 9;
        // temps stays null if not detected (optional for dressage)

        return $map;
    }

    private function normalizeForMatch(string $value): string
    {
        $value = mb_strtolower($value);
        // Remove accents
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        // Remove non-alphanumeric
        $value = preg_replace('/[^a-z0-9]/', '', $value);
        return $value;
    }

    private function calculerClassement(Championnat $championnat, $exclusionKeys, ?\Illuminate\Support\Collection $preloadedResultats = null): \Illuminate\Support\Collection
    {
        $allResultats = $preloadedResultats ?? $championnat->resultats()->with(['cavalier', 'cheval'])->get();

        $resultats1 = $allResultats
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->keyBy(fn ($r) => $r->cavalier_id . '-' . $r->cheval_id);

        $resultats2 = $allResultats
            ->where('epreuve_id', $championnat->epreuve2_id)
            ->keyBy(fn ($r) => $r->cavalier_id . '-' . $r->cheval_id);

        $classement = collect();

        foreach ($resultats1 as $key => $r1) {
            if (!$resultats2->has($key)) continue;

            $r2 = $resultats2[$key];
            $isExcluded = $exclusionKeys->has($key);

            $totalPoints = (float) $r1->points + (float) $r2->points;
            $totalTemps = ($r1->temps ?? 0) + ($r2->temps ?? 0);

            $classement->push([
                'cavalier_id' => $r1->cavalier_id,
                'cheval_id' => $r1->cheval_id,
                'cavalier_nom' => $r1->cavalier->nom,
                'cavalier_prenom' => $r1->cavalier->prenom,
                'cheval_nom' => $r1->cheval->nom,
                'club' => $r1->cavalier->club,
                'points_e1' => (float) $r1->points,
                'temps_e1' => $r1->temps,
                'statut_e1' => $r1->statut,
                'points_e2' => (float) $r2->points,
                'temps_e2' => $r2->temps,
                'statut_e2' => $r2->statut,
                'total_points' => $totalPoints,
                'total_temps' => $totalTemps,
                'is_excluded' => $isExcluded,
            ]);
        }

        // Sort: CSO = lowest total first; Hunter = highest total first
        if ($championnat->discipline->higherIsBetter()) {
            $classement = $classement->sortByDesc('total_points')->values();
        } else {
            $classement = $classement->sortBy([['total_points', 'asc'], ['total_temps', 'asc']])->values();
        }

        // Mark non-best results per cavalier as excluded (keep only best per cavalier)
        $bestCavalierSeen = [];
        $classement = $classement->map(function ($entry) use (&$bestCavalierSeen) {
            $cavId = $entry['cavalier_id'];
            if ($entry['is_excluded']) {
                return $entry;
            }
            if (isset($bestCavalierSeen[$cavId])) {
                $entry['is_excluded'] = true;
            } else {
                $bestCavalierSeen[$cavId] = true;
            }
            return $entry;
        });

        return $classement;
    }

    private function calculerClassementSimple(Championnat $championnat, $exclusionKeys, ?\Illuminate\Support\Collection $preloadedResultats = null): \Illuminate\Support\Collection
    {
        $allResultats = $preloadedResultats ?? $championnat->resultats()->with(['cavalier', 'cheval'])->get();

        $resultats1 = $allResultats->where('epreuve_id', $championnat->epreuve1_id);

        $classement = collect();

        foreach ($resultats1 as $r1) {
            $key = $r1->cavalier_id . '-' . $r1->cheval_id;
            $isExcluded = $exclusionKeys->has($key);

            // Dressage libre: +1 au pourcentage final
            $libreBonus = ($r1->libre && $championnat->discipline === DisciplineChampionnat::DRESSAGE) ? 1 : 0;
            $totalPoints = (float) $r1->points + $libreBonus;

            $classement->push([
                'cavalier_id' => $r1->cavalier_id,
                'cheval_id' => $r1->cheval_id,
                'cavalier_nom' => $r1->cavalier->nom,
                'cavalier_prenom' => $r1->cavalier->prenom,
                'cheval_nom' => $r1->cheval->nom,
                'club' => $r1->cavalier->club,
                'points_e1' => (float) $r1->points,
                'temps_e1' => $r1->temps,
                'statut_e1' => $r1->statut,
                'libre' => (bool) $r1->libre,
                'total_points' => $totalPoints,
                'total_temps' => $r1->temps ?? 0,
                'is_excluded' => $isExcluded,
            ]);
        }

        // Sort: CSO/default = lowest first; Dressage = highest first
        if ($championnat->discipline->higherIsBetter()) {
            $classement = $classement->sortByDesc('total_points')->values();
        } else {
            $classement = $classement->sortBy([['total_points', 'asc'], ['total_temps', 'asc']])->values();
        }

        // Mark non-best results per cavalier as excluded
        $bestCavalierSeen = [];
        $classement = $classement->map(function ($entry) use (&$bestCavalierSeen) {
            $cavId = $entry['cavalier_id'];
            if ($entry['is_excluded']) {
                return $entry;
            }
            if (isset($bestCavalierSeen[$cavId])) {
                $entry['is_excluded'] = true;
            } else {
                $bestCavalierSeen[$cavId] = true;
            }
            return $entry;
        });

        return $classement;
    }

    private function exportResultatsManuel(Concours $concours, Championnat $championnat, $exclusionKeys)
    {
        $participants = $championnat->participants();
        $classement = $this->calculerClassementManuel($championnat, $participants, $exclusionKeys)
            ->filter(fn ($e) => !$e['is_excluded'] && $e['position'] > 0)
            ->values();

        $filename = 'classement_' . str_replace(' ', '_', $championnat->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($classement) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Classement', 'Cavalier', 'Club', 'Cheval'], ';');
            foreach ($classement as $entry) {
                fputcsv($handle, [
                    $entry['position'],
                    trim(($entry['cavalier_prenom'] ?? '') . ' ' . ($entry['cavalier_nom'] ?? '')),
                    $entry['club'] ?? '',
                    $entry['cheval_nom'],
                ], ';');
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportResultats(Concours $concours, Championnat $championnat)
    {
        $championnat->load(['epreuve1', 'epreuve2']);

        $exclusionKeys = $championnat->exclusions
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id)
            ->flip();

        // Classement manuel: export CSV simplifie (rang/cavalier/cheval/club)
        if ($championnat->discipline->isManualRanking()) {
            return $this->exportResultatsManuel($concours, $championnat, $exclusionKeys);
        }

        $hasE2 = $championnat->epreuve2_id !== null;

        $classement = $hasE2
            ? $this->calculerClassement($championnat, $exclusionKeys)
            : $this->calculerClassementSimple($championnat, $exclusionKeys);

        // Filter: only non-excluded entries (one per cavalier, best result)
        $exported = $classement->filter(fn ($e) => !$e['is_excluded'])->values();

        $filename = 'classement_' . str_replace(' ', '_', $championnat->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $usePct = $championnat->discipline->usesPercentage();
        $valLabel = $usePct ? '%' : 'Points';

        $callback = function () use ($exported, $championnat, $hasE2, $usePct, $valLabel) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            if ($hasE2) {
                $headerRow = [
                    'Classement', 'Cavalier', 'Club', 'Cheval',
                    $valLabel . ' ' . $championnat->epreuve1->numero,
                ];
                if (!$usePct) {
                    $headerRow[] = 'Temps ' . $championnat->epreuve1->numero;
                }
                $headerRow[] = $valLabel . ' ' . $championnat->epreuve2->numero;
                if (!$usePct) {
                    $headerRow[] = 'Temps ' . $championnat->epreuve2->numero;
                }
                $headerRow[] = 'Total ' . $valLabel;
                if (!$usePct) {
                    $headerRow[] = 'Total Temps';
                }
                fputcsv($handle, $headerRow, ';');
            } else {
                $headerRow = ['Classement', 'Cavalier', 'Club', 'Cheval', $valLabel];
                if (!$usePct) {
                    $headerRow[] = 'Temps';
                }
                if ($championnat->discipline === DisciplineChampionnat::DRESSAGE) {
                    $headerRow[] = 'Libre';
                    $headerRow[] = 'Total %';
                }
                fputcsv($handle, $headerRow, ';');
            }

            foreach ($exported as $index => $entry) {
                $ptsE1 = match ($entry['statut_e1']) {
                    'elimine' => 'EL',
                    'non_partant' => 'NP',
                    'abandon' => 'AB',
                    default => number_format($entry['points_e1'], 2, ',', ''),
                };

                $row = [
                    $index + 1,
                    $entry['cavalier_prenom'] . ' ' . $entry['cavalier_nom'],
                    $entry['club'] ?? '',
                    $entry['cheval_nom'],
                    $ptsE1,
                ];
                if (!$usePct) {
                    $row[] = $entry['temps_e1'] ? number_format($entry['temps_e1'], 2, ',', '') : '';
                }

                if ($championnat->discipline === DisciplineChampionnat::DRESSAGE) {
                    $row[] = ($entry['libre'] ?? false) ? 'Oui' : 'Non';
                    $row[] = number_format($entry['total_points'], 2, ',', '');
                }

                if ($hasE2) {
                    $ptsE2 = match ($entry['statut_e2']) {
                        'elimine' => 'EL',
                        'non_partant' => 'NP',
                        'abandon' => 'AB',
                        default => number_format($entry['points_e2'], 2, ',', ''),
                    };
                    $row[] = $ptsE2;
                    if (!$usePct) {
                        $row[] = $entry['temps_e2'] ? number_format($entry['temps_e2'], 2, ',', '') : '';
                    }
                    $row[] = number_format($entry['total_points'], 2, ',', '');
                    if (!$usePct) {
                        $row[] = number_format($entry['total_temps'], 2, ',', '');
                    }
                }

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLDP(Concours $concours, Championnat $championnat)
    {
        $championnat->load(['epreuve1', 'epreuve2']);

        // All engages of epreuve 2 with cavalier/cheval info
        $engagesE2 = DB::table('engagements')
            ->join('cavaliers', 'cavaliers.id', '=', 'engagements.cavalier_id')
            ->join('chevaux', 'chevaux.id', '=', 'engagements.cheval_id')
            ->where('engagements.epreuve_id', $championnat->epreuve2_id)
            ->select(
                'engagements.numero_depart',
                'cavaliers.id as cavalier_id',
                'cavaliers.nom as cavalier_nom',
                'cavaliers.prenom as cavalier_prenom',
                'cavaliers.club',
                'chevaux.id as cheval_id',
                'chevaux.nom as cheval_nom',
            )
            ->get();

        // Resultats epreuve 1: build classement
        $sortE1 = $championnat->discipline->higherIsBetter()
            ? [['points', 'desc']]
            : [['points', 'asc'], ['temps', 'asc']];
        $resultatsE1 = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->get()
            ->sortBy($sortE1)
            ->values();

        // Map couple key => classement rank
        $classementE1 = [];
        foreach ($resultatsE1 as $index => $r) {
            $key = $r->cavalier_id . '-' . $r->cheval_id;
            $classementE1[$key] = $index + 1;
        }

        // Participants of this championnat (couples in both epreuves)
        $participants = $championnat->participants();
        $participantKeys = $participants->map(fn ($p) => $p->cavalier_id . '-' . $p->cheval_id)->flip();

        // Exclusions
        $exclusionKeys = $championnat->exclusions
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id)
            ->flip();

        // Build rows with sort info
        $rows = $engagesE2->map(function ($e) use ($classementE1, $participantKeys, $exclusionKeys) {
            $coupleKey = $e->cavalier_id . '-' . $e->cheval_id;
            $clE1 = $classementE1[$coupleKey] ?? null;

            if ($participantKeys->has($coupleKey)) {
                if ($exclusionKeys->has($coupleKey)) {
                    $participation = 'Exclu';
                    $sortGroup = 1; // Excluded: after non-participants
                } else {
                    $participation = 'Oui';
                    $sortGroup = 2; // Participants: last, reverse E1 order
                }
            } else {
                $participation = 'Non';
                $sortGroup = 0; // Non-participants: first
            }

            return [
                'numero_depart' => $e->numero_depart,
                'cavalier_prenom' => $e->cavalier_prenom,
                'cavalier_nom' => $e->cavalier_nom,
                'club' => $e->club,
                'cheval_nom' => $e->cheval_nom,
                'classement_e1' => $clE1,
                'participation' => $participation,
                'sort_group' => $sortGroup,
            ];
        });

        // Sort: group 0 (Non) -> group 1 (Exclu) -> group 2 (Oui, reverse E1 classement)
        // Within group 2: highest classement first (worst result first, best last)
        // Within group 2 without E1 result: before those with results
        $rows = $rows->sort(function ($a, $b) {
            if ($a['sort_group'] !== $b['sort_group']) {
                return $a['sort_group'] <=> $b['sort_group'];
            }
            if ($a['sort_group'] === 2) {
                // Both participants: reverse E1 classement (highest rank number first)
                $aRank = $a['classement_e1'] ?? 0;
                $bRank = $b['classement_e1'] ?? 0;
                return $bRank <=> $aRank;
            }
            // Within non-participants or excluded: by name
            return ($a['cavalier_nom'] . $a['cavalier_prenom']) <=> ($b['cavalier_nom'] . $b['cavalier_prenom']);
        })->values();

        $filename = 'LDP_' . str_replace(' ', '_', $championnat->nom) . '_E2.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Numero Depart', 'Numero FFE', 'Cavalier', 'Club', 'Cheval',
                'Classement epreuve 1', 'Participation Championnat',
            ], ';');

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['numero_depart'] ?? '',
                    $row['cavalier_prenom'] . ' ' . $row['cavalier_nom'],
                    $row['club'] ?? '',
                    $row['cheval_nom'],
                    $row['classement_e1'] ?? '',
                    $row['participation'],
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function deleteResultats(Request $request, Concours $concours, Championnat $championnat)
    {
        $request->validate([
            'epreuve' => 'required|in:1,2',
        ]);

        $epreuveId = $request->input('epreuve') == '1'
            ? $championnat->epreuve1_id
            : $championnat->epreuve2_id;

        $count = $championnat->resultats()->where('epreuve_id', $epreuveId)->count();
        $championnat->resultats()->where('epreuve_id', $epreuveId)->delete();

        return redirect()->route('concours.championnats.show', [$concours, $championnat])
            ->with('success', "$count résultats supprimés pour l'épreuve $request->epreuve.");
    }

    public function doublons(Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $championnats = $concours->championnats()->with(['epreuve1', 'epreuve2'])->get();

        // Existing exclusions keyed by "coupleKey-championnatId"
        $existingExclusions = ChampionnatExclusion::whereIn('championnat_id', $championnats->pluck('id'))
            ->get()
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id . '-' . $e->championnat_id)
            ->flip();

        $coupleChampionnats = [];

        // Pre-load all participants to avoid N+1 queries per championnat
        $participantsByChampionnat = $championnats->mapWithKeys(fn ($c) => [$c->id => $c->participants()]);

        foreach ($championnats as $championnat) {
            $participants = $participantsByChampionnat[$championnat->id];

            foreach ($participants as $participant) {
                $key = $participant->cavalier_id . '-' . $participant->cheval_id;

                if (!isset($coupleChampionnats[$key])) {
                    $coupleChampionnats[$key] = [
                        'cavalier_id' => $participant->cavalier_id,
                        'cheval_id' => $participant->cheval_id,
                        'cavalier_prenom' => $participant->cavalier_prenom,
                        'cavalier_nom' => $participant->cavalier_nom,
                        'club' => $participant->club,
                        'cheval_nom' => $participant->cheval_nom,
                        'championnats' => [],
                    ];
                }

                $coupleChampionnats[$key]['championnats'][] = [
                    'id' => $championnat->id,
                    'nom' => $championnat->nom,
                    'discipline' => $championnat->discipline->value,
                ];
            }
        }

        $doublons = collect($coupleChampionnats)
            ->filter(fn ($c) => count($c['championnats']) > 1)
            ->sortBy('cavalier_nom')
            ->values();

        return view('concours.championnats.doublons', compact('concours', 'championnats', 'doublons', 'existingExclusions'));
    }

    public function storeDoublons(Request $request, Concours $concours)
    {
        $selections = $request->input('selections', []);
        $allCouples = $request->input('couples', []);
        $championnats = $concours->championnats()->get();
        $championnatIds = $championnats->pluck('id');

        // Clear existing exclusions for this concours
        ChampionnatExclusion::whereIn('championnat_id', $championnatIds)->delete();

        // Ensure all couples are processed (even those with no checkbox checked)
        foreach ($allCouples as $coupleKey) {
            if (!isset($selections[$coupleKey])) {
                $selections[$coupleKey] = [];
            }
        }

        // Pre-load all participants per championnat to avoid N+1
        $participantsByChampionnat = $championnats->mapWithKeys(fn ($c) => [
            $c->id => $c->participants()->map(fn ($p) => $p->cavalier_id . '-' . $p->cheval_id)->flip(),
        ]);

        // For each couple, selections is an array of checked championnat IDs.
        // Unchecked championnats become exclusions.
        foreach ($selections as $coupleKey => $selectedChampionnatIds) {
            [$cavalierId, $chevalId] = explode('-', $coupleKey);

            $selectedIds = array_map('intval', (array) $selectedChampionnatIds);

            // Find all championnats this couple participates in
            $coupleChampionnatIds = [];
            foreach ($championnats as $championnat) {
                $isParticipant = $participantsByChampionnat[$championnat->id]->has($cavalierId . '-' . $chevalId);

                if ($isParticipant) {
                    $coupleChampionnatIds[] = $championnat->id;
                }
            }

            // Create exclusions for championnats that were NOT checked
            foreach ($coupleChampionnatIds as $champId) {
                if (!in_array((int) $champId, $selectedIds, true)) {
                    ChampionnatExclusion::create([
                        'championnat_id' => $champId,
                        'cavalier_id' => $cavalierId,
                        'cheval_id' => $chevalId,
                    ]);
                }
            }
        }

        return redirect()->route('concours.championnats.doublons', $concours)
            ->with('success', 'Sélections enregistrées.');
    }

    public function toggleLibre(Request $request, Concours $concours, Championnat $championnat)
    {
        $request->validate([
            'cavalier_id' => 'required|integer',
            'cheval_id' => 'required|integer',
        ]);

        $resultat = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->where('cavalier_id', $request->cavalier_id)
            ->where('cheval_id', $request->cheval_id)
            ->first();

        if ($resultat) {
            $resultat->update(['libre' => !$resultat->libre]);
        }

        if ($request->wantsJson()) {
            return response()->json(['libre' => $resultat ? (bool) $resultat->libre : false]);
        }

        return redirect()->route('concours.championnats.show', [$concours, $championnat]);
    }

    public function destroy(Concours $concours, Championnat $championnat)
    {
        $championnat->delete();

        return redirect()->route('concours.championnats.index', $concours)
            ->with('success', 'Championnat supprime.');
    }

    /**
     * Genere un PDF imprimable (start list) a partir d'un CSV uploade
     * au format LDP export (Numero Depart, Numero FFE, Cavalier, Club, Cheval, ...).
     * L'utilisateur imprime / enregistre en PDF via le navigateur.
     */
    public function generateStartList(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'titre' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());

        // Detect encoding and convert to UTF-8
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", $content);
        $headerLine = trim($lines[0] ?? '');
        $separator = $this->detectCsvSeparator($headerLine);

        $headerCols = array_map(fn ($c) => trim($c), str_getcsv($headerLine, $separator));
        $colMap = $this->detectStartListColumns($headerCols);

        $rows = [];
        foreach ($lines as $lineIndex => $line) {
            if ($lineIndex === 0) continue;
            $line = trim($line);
            if ($line === '') continue;

            $cols = str_getcsv($line, $separator);
            $numero = $colMap['numero'] !== null ? trim($cols[$colMap['numero']] ?? '') : '';
            $numeroFfe = $colMap['numero_ffe'] !== null ? trim($cols[$colMap['numero_ffe']] ?? '') : '';
            $cavalier = $colMap['cavalier'] !== null ? trim($cols[$colMap['cavalier']] ?? '') : '';
            $club = $colMap['club'] !== null ? trim($cols[$colMap['club']] ?? '') : '';
            $cheval = $colMap['cheval'] !== null ? trim($cols[$colMap['cheval']] ?? '') : '';

            // Skip lignes vides (pas de cavalier ni cheval)
            if ($cavalier === '' && $cheval === '') continue;

            $rows[] = [
                'numero' => $numero,
                'numero_ffe' => $numeroFfe,
                'cavalier' => $cavalier,
                'club' => $club,
                'cheval' => $cheval,
            ];
        }

        $titre = $validated['titre'];
        $date = \Carbon\Carbon::parse($validated['date']);

        return view('concours.championnats.print-startlist', compact(
            'concours', 'rows', 'titre', 'date'
        ));
    }

    /**
     * Genere une start list PDF pour le speaker/jury d'un championnat CSO,
     * avec en plus les infos de resultat de l'epreuve 1 (classement, points, temps).
     */
    public function generateSpeakerStartList(Request $request, Concours $concours, Championnat $championnat)
    {
        if ($championnat->discipline !== \App\Enums\DisciplineChampionnat::CSO) {
            abort(404);
        }

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'titre' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", $content);
        $headerLine = trim($lines[0] ?? '');
        $separator = $this->detectCsvSeparator($headerLine);

        $headerCols = array_map(fn ($c) => trim($c), str_getcsv($headerLine, $separator));
        $colMap = $this->detectStartListColumns($headerCols);

        // Charger les resultats epreuve 1 (avec cavalier + cheval pour le matching)
        $allResultats = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->with(['cavalier', 'cheval'])
            ->get();

        // Classement CSO : points asc, temps asc ; uniquement statut = normal
        $sortedNormal = $allResultats
            ->where('statut', 'normal')
            ->sortBy([['points', 'asc'], ['temps', 'asc']])
            ->values();

        $lookup = [];
        foreach ($sortedNormal as $i => $r) {
            $entry = [
                'rang' => $i + 1,
                'points' => $r->points,
                'temps' => $r->temps,
                'statut' => $r->statut,
            ];
            $chevalN = $this->normalizeForMatch($r->cheval->nom ?? '');
            $cavN = $this->normalizeForMatch(($r->cavalier->nom ?? '') . ' ' . ($r->cavalier->prenom ?? ''));
            $cavN2 = $this->normalizeForMatch(($r->cavalier->prenom ?? '') . ' ' . ($r->cavalier->nom ?? ''));
            $lookup[$chevalN . '|' . $cavN] = $entry;
            $lookup[$chevalN . '|' . $cavN2] = $entry;
        }
        // Statuts non-normaux (eliminé/NP/abandon) : pas de rang mais on remonte le statut
        foreach ($allResultats->where('statut', '!=', 'normal') as $r) {
            $entry = [
                'rang' => null,
                'points' => null,
                'temps' => null,
                'statut' => $r->statut,
            ];
            $chevalN = $this->normalizeForMatch($r->cheval->nom ?? '');
            $cavN = $this->normalizeForMatch(($r->cavalier->nom ?? '') . ' ' . ($r->cavalier->prenom ?? ''));
            $cavN2 = $this->normalizeForMatch(($r->cavalier->prenom ?? '') . ' ' . ($r->cavalier->nom ?? ''));
            $lookup[$chevalN . '|' . $cavN] = $entry;
            $lookup[$chevalN . '|' . $cavN2] = $entry;
        }

        $rows = [];
        foreach ($lines as $lineIndex => $line) {
            if ($lineIndex === 0) continue;
            $line = trim($line);
            if ($line === '') continue;

            $cols = str_getcsv($line, $separator);
            $numero = $colMap['numero'] !== null ? trim($cols[$colMap['numero']] ?? '') : '';
            $numeroFfe = $colMap['numero_ffe'] !== null ? trim($cols[$colMap['numero_ffe']] ?? '') : '';
            $cavalier = $colMap['cavalier'] !== null ? trim($cols[$colMap['cavalier']] ?? '') : '';
            $club = $colMap['club'] !== null ? trim($cols[$colMap['club']] ?? '') : '';
            $cheval = $colMap['cheval'] !== null ? trim($cols[$colMap['cheval']] ?? '') : '';

            if ($cavalier === '' && $cheval === '') continue;

            $matchKey = $this->normalizeForMatch($cheval) . '|' . $this->normalizeForMatch($cavalier);
            $res = $lookup[$matchKey] ?? null;

            $rows[] = [
                'numero' => $numero,
                'numero_ffe' => $numeroFfe,
                'cavalier' => $cavalier,
                'club' => $club,
                'cheval' => $cheval,
                'rang_e1' => $res['rang'] ?? null,
                'points_e1' => $res['points'] ?? null,
                'temps_e1' => $res['temps'] ?? null,
                'statut_e1' => $res['statut'] ?? null,
            ];
        }

        $titre = $validated['titre'];
        $date = \Carbon\Carbon::parse($validated['date']);

        return view('concours.championnats.print-speaker-startlist', compact(
            'concours', 'championnat', 'rows', 'titre', 'date'
        ));
    }

    /**
     * Detecte les index des colonnes dans un CSV de start list (format LDP export):
     * Numero Depart, Numero FFE, Cavalier, Club, Cheval.
     * Tolere variations de casse/accents/ordre.
     */
    private function detectStartListColumns(array $headerCols): array
    {
        $map = ['numero' => null, 'numero_ffe' => null, 'cavalier' => null, 'club' => null, 'cheval' => null];

        foreach ($headerCols as $i => $col) {
            $clean = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim(
                @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $col) ?: $col
            )));

            if ($map['numero_ffe'] === null && (str_contains($clean, 'numeroffe') || str_contains($clean, 'numffe') || $clean === 'ffe')) {
                $map['numero_ffe'] = $i;
            } elseif ($map['numero'] === null && (str_contains($clean, 'numerodepart') || $clean === 'numero' || $clean === 'no' || $clean === 'rang' || $clean === 'ordre')) {
                $map['numero'] = $i;
            } elseif ($map['cheval'] === null && str_contains($clean, 'cheval')) {
                $map['cheval'] = $i;
            } elseif ($map['cavalier'] === null && str_contains($clean, 'cavalier')) {
                $map['cavalier'] = $i;
            } elseif ($map['club'] === null && str_contains($clean, 'club')) {
                $map['club'] = $i;
            }
        }

        // Fallback aux positions du format LDP export:
        // 0: Numero Depart, 1: Numero FFE, 2: Cavalier, 3: Club, 4: Cheval
        if ($map['numero'] === null) $map['numero'] = 0;
        if ($map['numero_ffe'] === null) $map['numero_ffe'] = 1;
        if ($map['cavalier'] === null) $map['cavalier'] = 2;
        if ($map['club'] === null) $map['club'] = 3;
        if ($map['cheval'] === null) $map['cheval'] = 4;

        return $map;
    }
}
