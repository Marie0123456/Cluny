<?php

namespace App\Http\Controllers;

use App\Models\Championnat;
use App\Models\ChampionnatExclusion;
use App\Models\Concours;
use Illuminate\Http\Request;

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

        return view('concours.championnats.show', compact('concours', 'championnat', 'participants', 'exclusionKeys'));
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
