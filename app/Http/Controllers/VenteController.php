<?php

namespace App\Http\Controllers;

use App\Models\ClientFacturation;
use App\Models\Concours;
use App\Models\Produit;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function index(Concours $concours)
    {
        $ventes = $concours->ventes()
            ->with(['lignes.produit', 'clientFacturation'])
            ->latest()
            ->get();

        $totalGeneral = $ventes->sum('total_ttc');

        return view('concours.ventes.index', compact('concours', 'ventes', 'totalGeneral'));
    }

    public function create(Concours $concours)
    {
        $produits = Produit::where('actif', true)->orderBy('nom')->get();

        return view('concours.ventes.create', compact('concours', 'produits'));
    }

    public function store(Request $request, Concours $concours)
    {
        $validated = $request->validate([
            'nom_client' => 'required|string|max:255',
            'jour_paiement' => 'nullable|date',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'numero_cheque' => 'nullable|string|required_if:paiement_cheque,true',
            'facture' => 'boolean',
            'nom_facturation' => 'nullable|required_if:facture,true|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $concours, $request) {
            $clientFacturationId = null;
            if ($request->boolean('facture') && !empty($validated['nom_facturation'])) {
                $client = ClientFacturation::updateOrCreate(
                    ['nom' => $validated['nom_facturation']],
                    [
                        'telephone' => $validated['telephone'] ?? null,
                        'email' => $validated['email'] ?? null,
                        'adresse' => $validated['adresse'] ?? null,
                    ]
                );
                $clientFacturationId = $client->id;
            }

            $vente = Vente::create([
                'concours_id' => $concours->id,
                'nom_client' => $validated['nom_client'],
                'jour_paiement' => $validated['jour_paiement'] ?? null,
                'paiement_cb' => $request->boolean('paiement_cb'),
                'paiement_especes' => $request->boolean('paiement_especes'),
                'paiement_cheque' => $request->boolean('paiement_cheque'),
                'numero_cheque' => $validated['numero_cheque'] ?? null,
                'facture' => $request->boolean('facture'),
                'client_facturation_id' => $clientFacturationId,
                'commentaire' => $validated['commentaire'] ?? null,
                'total_ttc' => 0,
            ]);

            foreach ($validated['lignes'] as $ligne) {
                $produit = Produit::find($ligne['produit_id']);
                $totalLigne = $produit->prix_ttc * $ligne['quantite'];

                VenteLigne::create([
                    'vente_id' => $vente->id,
                    'produit_id' => $produit->id,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire_ttc' => $produit->prix_ttc,
                    'total_ttc' => $totalLigne,
                ]);
            }

            $vente->recalculerTotal();
        });

        return redirect()->route('concours.ventes.index', $concours)
            ->with('success', 'Vente enregistrée avec succès.');
    }

    public function show(Vente $vente)
    {
        $vente->load('lignes.produit', 'clientFacturation', 'concours');

        return view('concours.ventes.show', compact('vente'));
    }

    public function edit(Vente $vente)
    {
        $vente->load('lignes.produit', 'clientFacturation', 'concours');
        $produits = Produit::where('actif', true)->orderBy('nom')->get();

        return view('concours.ventes.edit', compact('vente', 'produits'));
    }

    public function update(Request $request, Vente $vente)
    {
        $validated = $request->validate([
            'nom_client' => 'required|string|max:255',
            'jour_paiement' => 'nullable|date',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'numero_cheque' => 'nullable|string|required_if:paiement_cheque,true',
            'facture' => 'boolean',
            'nom_facturation' => 'nullable|required_if:facture,true|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $vente, $request) {
            $clientFacturationId = null;
            if ($request->boolean('facture') && !empty($validated['nom_facturation'])) {
                $client = ClientFacturation::updateOrCreate(
                    ['nom' => $validated['nom_facturation']],
                    [
                        'telephone' => $validated['telephone'] ?? null,
                        'email' => $validated['email'] ?? null,
                        'adresse' => $validated['adresse'] ?? null,
                    ]
                );
                $clientFacturationId = $client->id;
            }

            $vente->update([
                'nom_client' => $validated['nom_client'],
                'jour_paiement' => $validated['jour_paiement'] ?? null,
                'paiement_cb' => $request->boolean('paiement_cb'),
                'paiement_especes' => $request->boolean('paiement_especes'),
                'paiement_cheque' => $request->boolean('paiement_cheque'),
                'numero_cheque' => $validated['numero_cheque'] ?? null,
                'facture' => $request->boolean('facture'),
                'client_facturation_id' => $clientFacturationId,
                'commentaire' => $validated['commentaire'] ?? null,
            ]);

            $vente->lignes()->delete();

            foreach ($validated['lignes'] as $ligne) {
                $produit = Produit::find($ligne['produit_id']);
                $totalLigne = $produit->prix_ttc * $ligne['quantite'];

                VenteLigne::create([
                    'vente_id' => $vente->id,
                    'produit_id' => $produit->id,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire_ttc' => $produit->prix_ttc,
                    'total_ttc' => $totalLigne,
                ]);
            }

            $vente->recalculerTotal();
        });

        return redirect()->route('concours.ventes.index', $vente->concours)
            ->with('success', 'Vente modifiée avec succès.');
    }

    public function destroy(Vente $vente)
    {
        $concours = $vente->concours;
        $vente->delete();

        return redirect()->route('concours.ventes.index', $concours)
            ->with('success', 'Vente supprimée.');
    }
}
