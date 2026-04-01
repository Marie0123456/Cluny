<?php

namespace App\Http\Controllers;

use App\Models\ClientFacturation;
use App\Models\CommandeRetrait;
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

        $caisseData = $ventes->map(fn ($v) => [
            'jour' => $v->jour_paiement?->format('Y-m-d'),
            'total' => (float) $v->total_ttc,
            'cb' => (bool) $v->paiement_cb,
            'especes' => (bool) $v->paiement_especes,
            'cheque' => (bool) $v->paiement_cheque,
            'internet' => (bool) $v->paiement_internet,
            'virement' => (bool) $v->paiement_virement,
        ]);

        return view('concours.ventes.index', compact('concours', 'ventes', 'totalGeneral', 'caisseData'));
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
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
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
            'a_retirer' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $concours, $request) {
            $aRetirer = $request->boolean('a_retirer');
            $clientFacturationId = null;
            if ($request->boolean('facture') && !empty($validated['nom_facturation'])) {
                $client = ClientFacturation::updateOrCreateByNom(
                    $validated['nom_facturation'],
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
                'paiement_internet' => $request->boolean('paiement_internet'),
                'paiement_virement' => $request->boolean('paiement_virement'),
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

            if ($aRetirer) {
                $today = now()->format('Y-m-d');
                $maxSeq = CommandeRetrait::where('numero_commande', 'like', $today . '-%')
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(numero_commande, '-', -1) AS UNSIGNED)) as max_seq")
                    ->value('max_seq');

                $nextSeq = ($maxSeq ?? 0) + 1;

                foreach ($validated['lignes'] as $ligne) {
                    $produit = Produit::find($ligne['produit_id']);
                    CommandeRetrait::create([
                        'concours_id' => $concours->id,
                        'numero_commande' => $today . '-' . $nextSeq,
                        'date_commande' => $today,
                        'prenom' => '',
                        'nom' => $validated['nom_client'],
                        'produit' => $produit->nom,
                        'quantite' => $ligne['quantite'],
                        'emplacement_boxes' => null,
                        'retire' => false,
                    ]);
                    $nextSeq++;
                }
            }
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

        $initialLignes = $vente->lignes->map(fn ($l) => [
            'produit_id' => (string) $l->produit_id,
            'quantite' => $l->quantite,
            'total' => (float) $l->total_ttc,
        ]);

        return view('concours.ventes.edit', compact('vente', 'produits', 'initialLignes'));
    }

    public function update(Request $request, Vente $vente)
    {
        $validated = $request->validate([
            'nom_client' => 'required|string|max:255',
            'jour_paiement' => 'nullable|date',
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
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
                $client = ClientFacturation::updateOrCreateByNom(
                    $validated['nom_facturation'],
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
                'paiement_internet' => $request->boolean('paiement_internet'),
                'paiement_virement' => $request->boolean('paiement_virement'),
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

    public function exportCsv(Concours $concours)
    {
        $ventes = $concours->ventes()
            ->with(['lignes.produit', 'clientFacturation'])
            ->latest()
            ->get();

        $filename = 'ventes_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($ventes) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Client', 'Date paiement', 'Produit', 'Qte',
                'P.U. HT', 'P.U. TTC', 'TVA %',
                'Total HT', 'Total TTC',
                'Paiement', 'N° Cheque',
                'Facture', 'Nom facturation', 'Telephone', 'Email', 'Adresse',
            ], ';');

            foreach ($ventes as $vente) {
                $moyens = [];
                if ($vente->paiement_cb) $moyens[] = 'CB';
                if ($vente->paiement_especes) $moyens[] = 'Especes';
                if ($vente->paiement_cheque) $moyens[] = 'Cheque';
                if ($vente->paiement_internet) $moyens[] = 'Internet';
                if ($vente->paiement_virement) $moyens[] = 'Virement';
                $paiementStr = implode(', ', $moyens);

                foreach ($vente->lignes as $index => $ligne) {
                    $tva = (float) $ligne->produit->tva;
                    $puHt = round($ligne->prix_unitaire_ttc / (1 + $tva / 100), 2);
                    $totalLigneTtc = (float) $ligne->total_ttc;
                    $totalLigneHt = round($totalLigneTtc / (1 + $tva / 100), 2);

                    $row = [
                        $index === 0 ? $vente->nom_client : '',
                        $index === 0 ? ($vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '') : '',
                        $ligne->produit->nom,
                        $ligne->quantite,
                        number_format($puHt, 2, ',', ''),
                        number_format((float) $ligne->prix_unitaire_ttc, 2, ',', ''),
                        number_format($tva, 1, ',', ''),
                        number_format($totalLigneHt, 2, ',', ''),
                        number_format($totalLigneTtc, 2, ',', ''),
                        $index === 0 ? $paiementStr : '',
                        $index === 0 ? ($vente->numero_cheque ?? '') : '',
                        $index === 0 ? ($vente->facture ? 'Oui' : 'Non') : '',
                        $index === 0 ? ($vente->clientFacturation->nom ?? '') : '',
                        $index === 0 ? ($vente->clientFacturation->telephone ?? '') : '',
                        $index === 0 ? ($vente->clientFacturation->email ?? '') : '',
                        $index === 0 ? ($vente->clientFacturation->adresse ?? '') : '',
                    ];

                    fputcsv($handle, $row, ';');
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
