<?php

namespace App\Http\Controllers;

use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Modification;
use Illuminate\Http\Request;

class ModificationController extends Controller
{
    public function index(Concours $concours)
    {
        $modifications = $concours->modifications()
            ->with(['engagement.epreuve', 'engagement.cavalier', 'ancienCheval', 'nouveauCheval'])
            ->latest()
            ->get();

        $epreuves = $concours->epreuves()
            ->with(['engagements.cavalier', 'engagements.cheval'])
            ->orderByRaw('CAST(numero AS UNSIGNED), numero')
            ->get();

        $epreuvesJson = $epreuves->map(function ($e) {
            return [
                'id' => $e->id,
                'engagements' => $e->engagements->map(function ($eng) {
                    return [
                        'engagement_id' => $eng->id,
                        'numero_depart' => $eng->numero_depart ?? '',
                        'cavalier_nom' => $eng->cavalier?->nom ?? '',
                        'cavalier_prenom' => $eng->cavalier?->prenom ?? '',
                        'cheval_nom' => $eng->cheval?->nom ?? '',
                        'cheval_num_sire' => $eng->cheval?->num_sire ?? '',
                    ];
                })->values(),
            ];
        })->values();

        return view('concours.modifications.index', compact('concours', 'modifications', 'epreuves', 'epreuvesJson'));
    }

    public function changementCheval(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
            'nouveau_cheval_id' => 'nullable|exists:chevaux,id',
            'nouveau_cheval_nom' => 'nullable|required_without:nouveau_cheval_id|string|max:255',
            'nouveau_cheval_num_sire' => 'nullable|string|max:255',
        ]);

        $engagement = Engagement::findOrFail($validated['engagement_id']);
        $ancienChevalId = $engagement->cheval_id;

        if (!empty($validated['nouveau_cheval_id'])) {
            $nouveauCheval = Cheval::findOrFail($validated['nouveau_cheval_id']);
        } else {
            $nouveauCheval = Cheval::firstOrCreate(
                [
                    'nom' => $validated['nouveau_cheval_nom'],
                    'num_sire' => $validated['nouveau_cheval_num_sire'] ?: null,
                ]
            );
        }

        Modification::create([
            'engagement_id' => $engagement->id,
            'concours_id' => $concours->id,
            'type' => 'changement_cheval',
            'description' => "Changement: {$engagement->cheval->nom} → {$nouveauCheval->nom}",
            'ancien_cheval_id' => $ancienChevalId,
            'nouveau_cheval_id' => $nouveauCheval->id,
            'statut' => 'en_attente',
        ]);

        $engagement->update(['cheval_id' => $nouveauCheval->id]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', 'Changement de cheval enregistre.');
    }

    public function marquerFait(Modification $modification)
    {
        $modification->update(['statut' => 'fait']);

        return redirect()->back()->with('success', 'Modification marquee comme faite.');
    }

    public function destroy(Modification $modification)
    {
        if ($modification->type === 'changement_cheval' && $modification->ancien_cheval_id) {
            $modification->engagement->update([
                'cheval_id' => $modification->ancien_cheval_id,
            ]);
        }

        $modification->update(['statut' => 'supprime']);

        return redirect()->back()->with('success', 'Modification annulee, ancien cheval restaure.');
    }
}
