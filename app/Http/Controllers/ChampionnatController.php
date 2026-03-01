<?php

namespace App\Http\Controllers;

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
            ->orderByRaw('CAST(numero AS UNSIGNED), numero')
            ->get();

        return view('concours.championnats.index', compact('concours', 'championnats', 'epreuves'));
    }

    public function store(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'epreuve1_id' => 'required|exists:epreuves,id',
            'epreuve2_id' => 'required|exists:epreuves,id|different:epreuve1_id',
        ]);

        $concours->championnats()->create($validated);

        return redirect()->route('concours.championnats.index', $concours)
            ->with('success', 'Championnat cree avec succes.');
    }

    public function show(Concours $concours, Championnat $championnat)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $championnat->load(['epreuve1', 'epreuve2']);
        $participants = $championnat->participants();

        $exclusionKeys = $championnat->exclusions
            ->map(fn ($e) => $e->cavalier_id . '-' . $e->cheval_id)
            ->flip();

        // Load resultats for each epreuve
        $resultatsEpreuve1 = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->with(['cavalier', 'cheval'])
            ->get()
            ->sortBy([['points', 'asc'], ['temps', 'asc']])
            ->values();

        $resultatsEpreuve2 = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve2_id)
            ->with(['cavalier', 'cheval'])
            ->get()
            ->sortBy([['points', 'asc'], ['temps', 'asc']])
            ->values();

        // Calculate classement general if both epreuves have resultats
        $classementGeneral = collect();
        if ($resultatsEpreuve1->isNotEmpty() && $resultatsEpreuve2->isNotEmpty()) {
            $classementGeneral = $this->calculerClassement($championnat, $exclusionKeys);
        }

        return view('concours.championnats.show', compact(
            'concours', 'championnat', 'participants', 'exclusionKeys',
            'resultatsEpreuve1', 'resultatsEpreuve2', 'classementGeneral'
        ));
    }

    public function importResultats(Request $request, Concours $concours, Championnat $championnat)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'epreuve' => 'required|in:1,2',
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

            $cols = str_getcsv($line, ';');

            // Skip header row and empty rows
            if ($lineIndex === 0) continue;
            if (count($cols) < 10) continue;

            $classement = trim($cols[0] ?? '');
            if ($classement === '' || !is_numeric($classement)) continue;

            $chevalNom = trim($cols[2] ?? '');
            $cavalierNom = trim($cols[5] ?? '');
            $pointsRaw = trim($cols[9] ?? '');
            $tempsRaw = trim($cols[11] ?? '');

            if ($chevalNom === '' || $cavalierNom === '') continue;

            // Parse points and statut
            $statut = 'normal';
            $points = 0;
            $temps = null;

            $pointsLower = mb_strtolower($pointsRaw);
            if (str_contains($pointsLower, 'elimin') || str_contains($pointsLower, 'limin')) {
                $statut = 'elimine';
                $points = 50;
            } elseif (str_contains($pointsLower, 'non') && str_contains($pointsLower, 'partant')) {
                $statut = 'non_partant';
                $points = 50;
            } elseif (str_contains($pointsLower, 'abandon')) {
                $statut = 'abandon';
                $points = 50;
            } else {
                $points = (float) str_replace(',', '.', $pointsRaw);
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
            $message .= ' ' . count($skipped) . ' non trouves : ' . implode(', ', $skipped);
        }

        return redirect()->route('concours.championnats.show', [$concours, $championnat])
            ->with('success', $message);
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

    private function calculerClassement(Championnat $championnat, $exclusionKeys): \Illuminate\Support\Collection
    {
        $resultats1 = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve1_id)
            ->with(['cavalier', 'cheval'])
            ->get()
            ->keyBy(fn ($r) => $r->cavalier_id . '-' . $r->cheval_id);

        $resultats2 = $championnat->resultats()
            ->where('epreuve_id', $championnat->epreuve2_id)
            ->with(['cavalier', 'cheval'])
            ->get()
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

        return $classement->sortBy([['total_points', 'asc'], ['total_temps', 'asc']])->values();
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

        foreach ($championnats as $championnat) {
            $participants = $championnat->participants();

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
                ];
            }
        }

        $doublons = collect($coupleChampionnats)
            ->filter(fn ($c) => count($c['championnats']) > 1)
            ->sortBy('cavalier_nom')
            ->values();

        return view('concours.championnats.doublons', compact('concours', 'doublons', 'existingExclusions'));
    }

    public function storeDoublons(Request $request, Concours $concours)
    {
        $selections = $request->input('selections', []);
        $championnats = $concours->championnats()->get();
        $championnatIds = $championnats->pluck('id');

        // Clear existing exclusions for this concours
        ChampionnatExclusion::whereIn('championnat_id', $championnatIds)->delete();

        // For each couple, the selected value is the championnat they DO participate in.
        // All others become exclusions.
        foreach ($selections as $coupleKey => $selectedChampionnatId) {
            [$cavalierId, $chevalId] = explode('-', $coupleKey);

            // Find all championnats this couple participates in
            $coupleChampionnatIds = [];
            foreach ($championnats as $championnat) {
                $isParticipant = $championnat->participants()
                    ->where('cavalier_id', $cavalierId)
                    ->where('cheval_id', $chevalId)
                    ->isNotEmpty();

                if ($isParticipant) {
                    $coupleChampionnatIds[] = $championnat->id;
                }
            }

            // Create exclusions for all championnats except the selected one
            foreach ($coupleChampionnatIds as $champId) {
                if ((int) $champId !== (int) $selectedChampionnatId) {
                    ChampionnatExclusion::create([
                        'championnat_id' => $champId,
                        'cavalier_id' => $cavalierId,
                        'cheval_id' => $chevalId,
                    ]);
                }
            }
        }

        return redirect()->route('concours.championnats.doublons', $concours)
            ->with('success', 'Selections enregistrees.');
    }

    public function destroy(Concours $concours, Championnat $championnat)
    {
        $championnat->delete();

        return redirect()->route('concours.championnats.index', $concours)
            ->with('success', 'Championnat supprime.');
    }
}
