<?php

namespace App\Http\Controllers;

use App\Models\Championnat;
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

        return view('concours.championnats.show', compact('concours', 'championnat', 'participants'));
    }

    public function doublons(Concours $concours)
    {
        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        $championnats = $concours->championnats()->with(['epreuve1', 'epreuve2'])->get();

        $coupleChampionnats = [];

        foreach ($championnats as $championnat) {
            $participants = $championnat->participants();

            foreach ($participants as $participant) {
                $key = $participant->cavalier_id . '-' . $participant->cheval_id;

                if (!isset($coupleChampionnats[$key])) {
                    $coupleChampionnats[$key] = [
                        'cavalier_prenom' => $participant->cavalier_prenom,
                        'cavalier_nom' => $participant->cavalier_nom,
                        'club' => $participant->club,
                        'cheval_nom' => $participant->cheval_nom,
                        'championnats' => [],
                    ];
                }

                $coupleChampionnats[$key]['championnats'][] = $championnat->nom;
            }
        }

        $doublons = collect($coupleChampionnats)
            ->filter(fn ($c) => count($c['championnats']) > 1)
            ->sortBy('cavalier_nom')
            ->values();

        return view('concours.championnats.doublons', compact('concours', 'doublons'));
    }

    public function destroy(Concours $concours, Championnat $championnat)
    {
        $championnat->delete();

        return redirect()->route('concours.championnats.index', $concours)
            ->with('success', 'Championnat supprime.');
    }
}
