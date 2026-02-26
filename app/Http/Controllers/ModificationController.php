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
            ->orderBy('numero')
            ->get();

        return view('concours.modifications.index', compact('concours', 'modifications', 'epreuves'));
    }

    public function changementCheval(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
            'nouveau_cheval_id' => 'required|exists:chevaux,id',
        ]);

        $engagement = Engagement::findOrFail($validated['engagement_id']);
        $ancienChevalId = $engagement->cheval_id;
        $nouveauCheval = Cheval::findOrFail($validated['nouveau_cheval_id']);

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
