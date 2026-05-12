<?php

namespace App\Http\Controllers;

use App\Enums\ModificationType;
use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use App\Models\Modification;
use App\Http\Traits\HandlesPaiement;
use Illuminate\Http\Request;

class ModificationController extends Controller
{
    use HandlesPaiement;
    public function index(Concours $concours)
    {
        $modifications = $concours->modifications()
            ->with([
                'engagement.epreuve:id,numero,nom,date',
                'engagement.cavalier:id,nom,prenom,num_licence',
                'engagement.cheval:id,nom,num_sire',
                'ancienCheval:id,nom,num_sire',
                'nouveauCheval:id,nom,num_sire',
                'ancienCavalier:id,nom,prenom,num_licence',
                'nouveauCavalier:id,nom,prenom,num_licence',
                'linkedModification.engagement.epreuve:id,numero',
                'clientFacturation:id,nom,telephone,email,adresse',
                'createdByUser:id,name',
                'modifiedByUser:id,name',
                'doneByUser:id,name',
            ])
            ->join('engagements', 'modifications.engagement_id', '=', 'engagements.id')
            ->join('epreuves', 'engagements.epreuve_id', '=', 'epreuves.id')
            ->orderByRaw('CAST(epreuves.numero AS INTEGER), epreuves.numero')
            ->orderBy('modifications.created_at', 'desc')
            ->select('modifications.*')
            ->get();

        $epreuves = $concours->epreuves()
            ->with([
                'engagements:id,epreuve_id,cavalier_id,cheval_id,numero_depart,is_non_partant',
                'engagements.cavalier:id,nom,prenom,num_licence',
                'engagements.cheval:id,nom,num_sire',
            ])
            ->orderByRaw('CAST(numero AS INTEGER), numero')
            ->get();

        $epreuvesJson = $epreuves->map(function ($e) {
            return [
                'id' => $e->id,
                'prix' => $e->prix,
                'nom' => $e->nom,
                'type_detecte' => $e->type_detecte,
                'engagements' => $e->engagements->map(function ($eng) {
                    return [
                        'engagement_id' => $eng->id,
                        'numero_depart' => $eng->numero_depart ?? '',
                        'cavalier_id' => $eng->cavalier?->id,
                        'cavalier_nom' => $eng->cavalier?->nom ?? '',
                        'cavalier_prenom' => $eng->cavalier?->prenom ?? '',
                        'cheval_nom' => $eng->cheval?->nom ?? '',
                        'cheval_num_sire' => $eng->cheval?->num_sire ?? '',
                        'is_non_partant' => $eng->is_non_partant,
                    ];
                })->values(),
            ];
        })->values();

        // Tous les cavaliers uniques du concours (pour le formulaire invitation)
        $allCavaliersJson = $epreuves->flatMap(function ($e) {
            return $e->engagements->map(function ($eng) {
                return [
                    'cavalier_id' => $eng->cavalier?->id,
                    'cavalier_nom' => $eng->cavalier?->nom ?? '',
                    'cavalier_prenom' => $eng->cavalier?->prenom ?? '',
                    'num_licence' => $eng->cavalier?->num_licence ?? '',
                ];
            });
        })->filter(fn($c) => $c['cavalier_id'])->unique('cavalier_id')->sortBy('cavalier_nom')->values();

        return view('concours.modifications.index', compact('concours', 'modifications', 'epreuves', 'epreuvesJson', 'allCavaliersJson'));
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
        $this->ensureEngagementBelongsToConcours($engagement, $concours);
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
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        $engagement->update(['cheval_id' => $nouveauCheval->id]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', 'Changement de cheval enregistré.');
    }

    public function changementCavalier(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
            'nouveau_cavalier_id' => 'nullable|exists:cavaliers,id',
            'nouveau_cavalier_nom' => 'nullable|required_without:nouveau_cavalier_id|string|max:255',
            'nouveau_cavalier_prenom' => 'nullable|string|max:255',
            'nouveau_cavalier_num_licence' => 'nullable|string|max:255',
        ]);

        $engagement = Engagement::with(['cavalier', 'epreuve'])->findOrFail($validated['engagement_id']);
        $this->ensureEngagementBelongsToConcours($engagement, $concours);

        // Block pro events when concours is GN
        if ($concours->grand_national && $engagement->epreuve->type_detecte === 'pro') {
            return redirect()->back()->withErrors(['engagement_id' => 'Changement de cavalier non autorisé sur les épreuves Pro en Grand National.']);
        }

        $ancienCavalierId = $engagement->cavalier_id;

        if (!empty($validated['nouveau_cavalier_id'])) {
            $nouveauCavalier = Cavalier::findOrFail($validated['nouveau_cavalier_id']);
        } else {
            $nouveauCavalier = Cavalier::firstOrCreate(
                ['num_licence' => $validated['nouveau_cavalier_num_licence'] ?: null],
                [
                    'nom' => $validated['nouveau_cavalier_nom'],
                    'prenom' => $validated['nouveau_cavalier_prenom'] ?? '',
                ]
            );
        }

        Modification::create([
            'engagement_id' => $engagement->id,
            'concours_id' => $concours->id,
            'type' => ModificationType::CHANGEMENT_CAVALIER->value,
            'description' => "Changement cavalier: {$engagement->cavalier->nom} → {$nouveauCavalier->nom}",
            'ancien_cavalier_id' => $ancienCavalierId,
            'nouveau_cavalier_id' => $nouveauCavalier->id,
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        $engagement->update(['cavalier_id' => $nouveauCavalier->id]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', 'Changement de cavalier enregistré.');
    }

    public function invitation(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'epreuve_id' => 'required|exists:epreuves,id',
            'cavalier_id' => 'nullable|exists:cavaliers,id',
            'nouveau_cavalier_nom' => 'nullable|required_without:cavalier_id|string|max:255',
            'nouveau_cavalier_prenom' => 'nullable|string|max:255',
            'nouveau_cavalier_num_licence' => 'nullable|string|max:255',
            'cheval_id' => 'nullable|exists:chevaux,id',
            'nouveau_cheval_nom' => 'nullable|required_without:cheval_id|string|max:255',
            'nouveau_cheval_num_sire' => 'nullable|string|max:255',
            'type_compte' => 'nullable|in:Licence,Compte,Club',
            'numero_compte' => 'nullable|string|max:255',
            'is_gn' => 'boolean',
            'facture' => 'boolean',
            'nom_facturation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
            'prix' => 'nullable|numeric|min:0',
            'pf' => 'nullable|numeric|min:0',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'numero_cheque' => 'nullable|string|max:255',
            'jour_paiement' => 'nullable|date',
        ]);

        $epreuve = Epreuve::findOrFail($validated['epreuve_id']);
        abort_unless($epreuve->concours_id === $concours->id, 403, 'Cette épreuve n\'appartient pas à ce concours.');

        // Resolve or create cavalier
        if (!empty($validated['cavalier_id'])) {
            $cavalier = Cavalier::findOrFail($validated['cavalier_id']);
        } else {
            $cavalier = Cavalier::firstOrCreate(
                ['num_licence' => $validated['nouveau_cavalier_num_licence'] ?: null],
                [
                    'nom' => $validated['nouveau_cavalier_nom'],
                    'prenom' => $validated['nouveau_cavalier_prenom'] ?? '',
                ]
            );
        }

        // Resolve or create cheval
        if (!empty($validated['cheval_id'])) {
            $cheval = Cheval::findOrFail($validated['cheval_id']);
        } else {
            $cheval = Cheval::firstOrCreate(
                [
                    'nom' => $validated['nouveau_cheval_nom'],
                    'num_sire' => $validated['nouveau_cheval_num_sire'] ?: null,
                ]
            );
        }

        // Auto-assign numero_depart (cast to numeric to avoid string comparison: "9" > "80")
        $maxNumero = (int) $epreuve->engagements()->selectRaw("MAX(CASE WHEN numero_depart ~ '^[0-9]+$' THEN CAST(numero_depart AS INTEGER) END) as max_num")->value('max_num');
        $numeroDepart = $maxNumero + 1;

        // Create the engagement
        $engagement = Engagement::create([
            'epreuve_id' => $epreuve->id,
            'cavalier_id' => $cavalier->id,
            'cheval_id' => $cheval->id,
            'numero_depart' => $numeroDepart,
            'is_invitation' => true,
        ]);

        // Calculate prix and PF (use submitted values if provided, otherwise auto-calculate)
        $isGn = $request->boolean('is_gn');
        $typeDetecte = $epreuve->type_detecte;
        $epreuvePrix = (float) ($epreuve->prix ?? 0);

        if (isset($validated['prix']) && isset($validated['pf'])) {
            // Prix édité manuellement
            $prix = (float) $validated['prix'];
            $pf = (float) $validated['pf'];
        } elseif ($concours->type_ffe_sif) {
            // FFE SIF : +10€, PF 9.90€, pas de GN
            $prix = $epreuvePrix + config('ehnc.tarifs.invitation_supplement_sif');
            $pf = config('ehnc.tarifs.invitation_pf_sif');
        } elseif ($concours->grand_national && $isGn && $typeDetecte === 'pro') {
            // FFE Compet GN Pro
            $prix = $epreuvePrix;
            $pf = config('ehnc.tarifs.pf_grand_national');
        } else {
            // FFE Compet standard
            $prix = $epreuvePrix + config('ehnc.tarifs.invitation_supplement');
            $pf = config('ehnc.tarifs.invitation_pf');
        }

        // Handle facturation
        $clientFacturationId = $this->resolveClientFacturation($validated, $request->boolean('facture'));

        // Create modification record
        Modification::create([
            'engagement_id' => $engagement->id,
            'concours_id' => $concours->id,
            'type' => ModificationType::AJOUT_ENGAGEMENT->value,
            'description' => "Invitation: {$cavalier->prenom} {$cavalier->nom} sur {$cheval->nom} → Épreuve {$epreuve->numero}",
            'prix' => $prix,
            'pf' => $pf,
            'type_compte' => $validated['type_compte'],
            'numero_compte' => $validated['numero_compte'],
            'is_gn' => $isGn,
            'paiement_cb' => $request->boolean('paiement_cb'),
            'paiement_especes' => $request->boolean('paiement_especes'),
            'paiement_cheque' => $request->boolean('paiement_cheque'),
            'paiement_internet' => $request->boolean('paiement_internet'),
            'paiement_virement' => $request->boolean('paiement_virement'),
            'numero_cheque' => $validated['numero_cheque'] ?? null,
            'jour_paiement' => $validated['jour_paiement'] ?? null,
            'facture' => $request->boolean('facture'),
            'client_facturation_id' => $clientFacturationId,
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', 'Invitation enregistrée.');
    }

    public function changementEpreuve(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
            'nouvelle_epreuve_id' => 'required|exists:epreuves,id',
            'prix' => 'required|numeric|min:0',
            'pf' => 'required|numeric|min:0',
            'is_gn' => 'boolean',
            'type_compte' => 'nullable|in:Licence,Compte,Club',
            'numero_compte' => 'nullable|string|max:255',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'numero_cheque' => 'nullable|string|max:255',
            'jour_paiement' => 'nullable|date',
            'facture' => 'boolean',
            'nom_facturation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
        ]);

        $engagement = Engagement::with(['cavalier', 'cheval', 'epreuve'])->findOrFail($validated['engagement_id']);
        $this->ensureEngagementBelongsToConcours($engagement, $concours);
        $nouvelleEpreuve = Epreuve::findOrFail($validated['nouvelle_epreuve_id']);
        abort_unless($nouvelleEpreuve->concours_id === $concours->id, 403, 'Cette épreuve n\'appartient pas à ce concours.');

        // 1. Mark engagement as NP in old epreuve
        $engagement->update(['is_non_partant' => true]);

        $npMod = Modification::create([
            'engagement_id' => $engagement->id,
            'concours_id' => $concours->id,
            'type' => ModificationType::NON_PARTANT->value,
            'description' => "NP (changement épreuve): {$engagement->cavalier->nom} — Épreuve {$engagement->epreuve->numero}",
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        // 2. Create new engagement in new epreuve
        $maxNumero = (int) $nouvelleEpreuve->engagements()->selectRaw("MAX(CASE WHEN numero_depart ~ '^[0-9]+$' THEN CAST(numero_depart AS INTEGER) END) as max_num")->value('max_num');
        $numeroDepart = $maxNumero + 1;

        $newEngagement = Engagement::create([
            'epreuve_id' => $nouvelleEpreuve->id,
            'cavalier_id' => $engagement->cavalier_id,
            'cheval_id' => $engagement->cheval_id,
            'numero_depart' => $numeroDepart,
            'is_invitation' => true,
        ]);

        // Handle facturation
        $clientFacturationId = $this->resolveClientFacturation($validated, $request->boolean('facture'));

        // 3. Create changement d'epreuve modification linked to NP
        $changementMod = Modification::create([
            'engagement_id' => $newEngagement->id,
            'concours_id' => $concours->id,
            'type' => ModificationType::CHANGEMENT_EPREUVE->value,
            'description' => "Changement épreuve: {$engagement->epreuve->numero} → {$nouvelleEpreuve->numero}",
            'linked_modification_id' => $npMod->id,
            'prix' => $validated['prix'],
            'pf' => $validated['pf'],
            'is_gn' => $request->boolean('is_gn'),
            'type_compte' => $validated['type_compte'] ?? null,
            'numero_compte' => $validated['numero_compte'] ?? null,
            'paiement_cb' => $request->boolean('paiement_cb'),
            'paiement_especes' => $request->boolean('paiement_especes'),
            'paiement_cheque' => $request->boolean('paiement_cheque'),
            'paiement_internet' => $request->boolean('paiement_internet'),
            'paiement_virement' => $request->boolean('paiement_virement'),
            'numero_cheque' => $validated['numero_cheque'] ?? null,
            'jour_paiement' => $validated['jour_paiement'] ?? null,
            'facture' => $request->boolean('facture'),
            'client_facturation_id' => $clientFacturationId,
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        // Link NP to changement too
        $npMod->update(['linked_modification_id' => $changementMod->id]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', "Changement d'épreuve enregistré.");
    }

    public function nonPartant(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
        ]);

        $engagement = Engagement::with(['cavalier', 'epreuve'])->findOrFail($validated['engagement_id']);
        $this->ensureEngagementBelongsToConcours($engagement, $concours);
        $engagement->update(['is_non_partant' => true]);

        Modification::create([
            'engagement_id' => $engagement->id,
            'concours_id' => $concours->id,
            'type' => ModificationType::NON_PARTANT->value,
            'description' => "Non-partant: {$engagement->cavalier->prenom} {$engagement->cavalier->nom} — Épreuve {$engagement->epreuve->numero}",
            'statut' => 'cree',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('concours.modifications.index', $concours)
            ->with('success', 'Non-partant enregistré.');
    }

    public function updatePaiement(Request $request, Modification $modification)
    {
        $validated = $request->validate([
            'type_compte' => 'nullable|in:Licence,Compte,Club',
            'numero_compte' => 'nullable|string|max:255',
            'cheval_id' => 'nullable|exists:chevaux,id',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'numero_cheque' => 'nullable|string|max:255',
            'jour_paiement' => 'nullable|date',
            'facture' => 'boolean',
            'nom_facturation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
            'is_gn' => 'boolean',
        ]);

        $clientFacturationId = $this->resolveClientFacturation($validated, $request->boolean('facture'))
            ?? $modification->client_facturation_id;
        if (!$request->boolean('facture')) {
            $clientFacturationId = null;
        }

        $updateData = [
            'type_compte' => $validated['type_compte'] ?? null,
            'numero_compte' => $validated['numero_compte'] ?? null,
            'paiement_cb' => $request->boolean('paiement_cb'),
            'paiement_especes' => $request->boolean('paiement_especes'),
            'paiement_cheque' => $request->boolean('paiement_cheque'),
            'paiement_internet' => $request->boolean('paiement_internet'),
            'paiement_virement' => $request->boolean('paiement_virement'),
            'numero_cheque' => $validated['numero_cheque'] ?? null,
            'jour_paiement' => $validated['jour_paiement'] ?? null,
            'facture' => $request->boolean('facture'),
            'client_facturation_id' => $clientFacturationId,
            'is_gn' => $request->boolean('is_gn'),
        ];

        // Changement de cheval (invitations et changements de cheval)
        $chevalChanged = false;
        if (in_array($modification->type, [ModificationType::AJOUT_ENGAGEMENT, ModificationType::CHANGEMENT_CHEVAL])
            && !empty($validated['cheval_id'])
            && $modification->engagement
            && (int) $modification->engagement->cheval_id !== (int) $validated['cheval_id']
        ) {
            // Pour CHANGEMENT_CHEVAL, mettre à jour aussi nouveau_cheval_id et la description
            if ($modification->type === ModificationType::CHANGEMENT_CHEVAL) {
                $nouveauCheval = \App\Models\Cheval::find($validated['cheval_id']);
                $ancienCheval = $modification->ancienCheval;
                $updateData['nouveau_cheval_id'] = $validated['cheval_id'];
                $updateData['description'] = "Changement: " . ($ancienCheval->nom ?? '?') . " → " . ($nouveauCheval->nom ?? '?');
            }
            $modification->engagement->update(['cheval_id' => $validated['cheval_id']]);
            $chevalChanged = true;
        }

        // Ne marquer comme modifié que si type_compte, numero_compte ou cheval ont changé
        $compteChanged = ($validated['type_compte'] ?? null) !== $modification->type_compte
            || ($validated['numero_compte'] ?? null) !== $modification->numero_compte;

        if ($compteChanged || $chevalChanged) {
            if (in_array($modification->statut->value, ['fait', 'cree'])) {
                $updateData['statut'] = 'modifie';
            }
            $updateData['modified_by'] = auth()->id();
        }

        $modification->update($updateData);

        return redirect()->back()->with('success', 'Paiement mis à jour.');
    }

    public function marquerFait(Request $request, Modification $modification)
    {
        $modification->update([
            'statut' => 'fait',
            'done_by' => auth()->id(),
            'done_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Modification marquée comme faite.');
    }

    public function destroy(Modification $modification)
    {
        if ($modification->source_import) {
            abort(403, 'Les forfaits importés depuis FFE Compet ne peuvent pas être supprimés.');
        }

        // Premier clic : marquer "à supprimer" sans rien annuler
        if ($modification->statut->value !== 'a_supprimer') {
            $modification->update([
                'statut' => 'a_supprimer',
                'modified_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Modification marquée à supprimer.');
        }

        // Second clic : suppression définitive
        $modification->load('engagement');

        if ($modification->type->value === 'changement_cheval' && $modification->ancien_cheval_id) {
            $modification->engagement->update([
                'cheval_id' => $modification->ancien_cheval_id,
            ]);
        }

        if ($modification->type->value === 'changement_cavalier' && $modification->ancien_cavalier_id) {
            $modification->engagement->update([
                'cavalier_id' => $modification->ancien_cavalier_id,
            ]);
        }

        if ($modification->type->value === 'non_partant' && !$modification->linked_modification_id) {
            $modification->engagement->update([
                'is_non_partant' => false,
            ]);
        }

        if ($modification->type->value === 'ajout_engagement') {
            $modification->engagement->delete();
        }

        // Changement d'epreuve: cancel both NP and new engagement
        if ($modification->type->value === 'changement_epreuve') {
            // Delete the new engagement created
            $modification->engagement->delete();
            // Cancel the linked NP
            if ($modification->linked_modification_id) {
                $linked = Modification::with('engagement')->find($modification->linked_modification_id);
                if ($linked) {
                    $linked->engagement->update(['is_non_partant' => false]);
                    $linked->update(['statut' => 'supprime']);
                }
            }
        }

        $modification->update(['statut' => 'supprime']);

        return redirect()->back()->with('success', 'Modification annulée.');
    }

    private function ensureEngagementBelongsToConcours(Engagement $engagement, Concours $concours): void
    {
        $engagement->loadMissing('epreuve:id,concours_id');
        abort_unless($engagement->epreuve->concours_id === $concours->id, 403, 'Cet engagement n\'appartient pas à ce concours.');
    }
}
