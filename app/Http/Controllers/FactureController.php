<?php

namespace App\Http\Controllers;

use App\Models\ClientFacturation;
use App\Models\Concours;
use App\Models\Modification;
use App\Models\Vente;
use Illuminate\Http\Request;
use App\Http\Traits\HandlesPaiement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FactureController extends Controller
{
    use HandlesPaiement;
    public function index(Concours $concours)
    {
        $clients = ClientFacturation::whereHas('ventes', function ($q) use ($concours) {
            $q->where('concours_id', $concours->id);
        })
            ->orWhereHas('modifications', function ($q) use ($concours) {
                $q->where('concours_id', $concours->id)
                    ->where('statut', '!=', 'supprime');
            })
            ->withCount([
                'ventes' => fn($q) => $q->where('concours_id', $concours->id),
                'modifications' => fn($q) => $q->where('concours_id', $concours->id)->where('statut', '!=', 'supprime'),
            ])
            ->withSum(['ventes' => fn($q) => $q->where('concours_id', $concours->id)], 'total_ttc')
            ->withSum(['modifications' => fn($q) => $q->where('concours_id', $concours->id)->where('statut', '!=', 'supprime')], 'prix')
            ->orderBy('nom')
            ->get();

        // Caisse: ventes et modifications sans client_facturation_id
        $caisseVentesCount = Vente::where('concours_id', $concours->id)
            ->whereNull('client_facturation_id')
            ->count();

        $caisseModificationsCount = Modification::where('concours_id', $concours->id)
            ->where('statut', '!=', 'supprime')
            ->whereIn('type', ['ajout_engagement', 'changement_epreuve'])
            ->whereNull('client_facturation_id')
            ->count();

        $caisseTotal = Vente::where('concours_id', $concours->id)
                ->whereNull('client_facturation_id')
                ->sum('total_ttc')
            + Modification::where('concours_id', $concours->id)
                ->where('statut', '!=', 'supprime')
                ->whereIn('type', ['ajout_engagement', 'changement_epreuve'])
                ->whereNull('client_facturation_id')
                ->sum('prix');

        return view('concours.factures.index', compact(
            'concours', 'clients',
            'caisseVentesCount', 'caisseModificationsCount', 'caisseTotal'
        ));
    }

    public function show(Concours $concours, ClientFacturation $client)
    {
        [$ventes, $modifications, $totalVentes, $totalModifications] = $this->getClientData($concours, $client);

        return view('concours.factures.show', compact('concours', 'client', 'ventes', 'modifications', 'totalVentes', 'totalModifications'));
    }

    public function updatePaiementGlobal(Request $request, Concours $concours, ClientFacturation $client)
    {
        $validated = $request->validate([
            'paiement_cb' => 'boolean',
            'paiement_especes' => 'boolean',
            'paiement_cheque' => 'boolean',
            'paiement_internet' => 'boolean',
            'paiement_virement' => 'boolean',
            'numero_cheque' => 'nullable|string|required_if:paiement_cheque,true',
            'jour_paiement' => 'nullable|date',
        ]);

        $paiementData = [
            'paiement_cb' => $validated['paiement_cb'] ?? false,
            'paiement_especes' => $validated['paiement_especes'] ?? false,
            'paiement_cheque' => $validated['paiement_cheque'] ?? false,
            'paiement_internet' => $validated['paiement_internet'] ?? false,
            'paiement_virement' => $validated['paiement_virement'] ?? false,
            'numero_cheque' => $validated['numero_cheque'] ?? null,
            'jour_paiement' => $validated['jour_paiement'] ?? null,
        ];

        // Mettre à jour toutes les ventes du client pour ce concours
        $client->ventes()
            ->where('concours_id', $concours->id)
            ->update($paiementData);

        // Mettre à jour toutes les modifications payantes du client pour ce concours
        $client->modifications()
            ->where('concours_id', $concours->id)
            ->where('statut', '!=', 'supprime')
            ->update($paiementData);

        return redirect()->route('concours.factures.show', [$concours, $client])
            ->with('success', 'Paiement mis à jour pour toutes les lignes.');
    }

    public function caisse(Concours $concours)
    {
        $caisseData = $this->getCaisseData($concours);

        return view('concours.factures.caisse', compact('concours') + $caisseData);
    }

    public function exportCsv(Concours $concours): StreamedResponse
    {
        $clientsData = $this->getAllClientsData($concours);
        $caisseData = $this->getCaisseData($concours);
        $filename = 'factures_' . str_replace(' ', '_', $concours->nom) . '_' . $concours->date_debut->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($concours, $clientsData, $caisseData) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            $sep = ';';

            echo 'Factures - ' . $concours->nom . "\n";
            echo 'Du ' . $concours->date_debut->format('d/m/Y') . ' au ' . $concours->date_fin->format('d/m/Y') . "\n\n";

            $grandTotalVentes = 0;
            $grandTotalModifications = 0;

            foreach ($clientsData as $data) {
                $client = $data['client'];
                $ventes = $data['ventes'];
                $modifications = $data['modifications'];
                $totalVentes = $data['totalVentes'];
                $totalModifications = $data['totalModifications'];

                $grandTotalVentes += $totalVentes;
                $grandTotalModifications += $totalModifications;

                // Client header
                echo '=== ' . $client->nom . " ===\n";
                if ($client->telephone) echo 'Tel: ' . $client->telephone . "\n";
                if ($client->email) echo 'Email: ' . $client->email . "\n";

                // Ventes
                if ($ventes->isNotEmpty()) {
                    echo implode($sep, ['Nom facturation', 'Client', 'Produit', 'Qte', 'P.U. TTC', 'TVA %', 'Total HT', 'Total TTC', 'Paiement', 'Date']) . "\n";
                    foreach ($ventes as $vente) {
                        $paiementStr = $this->getPaiementLabel($vente);
                        $dateStr = $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '';

                        foreach ($vente->lignes as $index => $ligne) {
                            $totalHt = $this->calculateHtFromTtc((float) $ligne->total_ttc, (float) $ligne->produit->tva);
                            echo implode($sep, [
                                $index === 0 ? $client->nom : '',
                                $index === 0 ? $vente->nom_client : '',
                                $ligne->produit->nom,
                                $ligne->quantite,
                                number_format($ligne->prix_unitaire_ttc, 2, ',', ''),
                                number_format($ligne->produit->tva, 1, ',', ''),
                                number_format($totalHt, 2, ',', ''),
                                number_format($ligne->total_ttc, 2, ',', ''),
                                $index === 0 ? $paiementStr : '',
                                $index === 0 ? $dateStr : '',
                            ]) . "\n";
                        }
                    }
                }

                // Modifications
                if ($modifications->isNotEmpty()) {
                    echo implode($sep, ['Nom facturation', 'N. Épreuve', 'Cavalier', 'Cheval', 'Type', 'PF', 'P.U. HT', 'Prix TTC', 'Paiement', 'Date']) . "\n";
                    foreach ($modifications as $index => $mod) {
                        $puHt = $this->calculateModificationHt((float) $mod->prix, $mod->pf);

                        echo implode($sep, [
                            $index === 0 ? $client->nom : '',
                            $mod->engagement->epreuve->numero ?? '-',
                            trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                            $mod->engagement->cheval->nom ?? '-',
                            $mod->type->label(),
                            $mod->pf !== null ? number_format($mod->pf, 2, ',', '') : '',
                            $puHt !== '' ? number_format($puHt, 2, ',', '') : '',
                            $mod->prix ? number_format($mod->prix, 2, ',', '') : '',
                            $this->getPaiementLabel($mod),
                            $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '',
                        ]) . "\n";
                    }
                }

                echo 'Total ' . $client->nom . $sep . $sep . $sep . $sep . $sep . $sep . $sep . number_format($totalVentes + $totalModifications, 2, ',', '') . "\n";
                echo "\n";
            }

            // Caisse
            $caisseTotalVentes = $caisseData['totalCaisseVentes'];
            $caisseTotalMods = $caisseData['totalCaisseModifications'];
            $grandTotalVentes += $caisseTotalVentes;
            $grandTotalModifications += $caisseTotalMods;

            if ($caisseTotalVentes > 0 || $caisseTotalMods > 0) {
                echo "=== CAISSE ===\n";

                if ($caisseData['ventesGrouped']->isNotEmpty()) {
                    echo implode($sep, ['', 'Produit', 'Qte', 'P.U. TTC', 'TVA %', 'Total HT', 'Paiement', 'Total TTC']) . "\n";
                    foreach ($caisseData['ventesGrouped'] as $group) {
                        echo implode($sep, [
                            '',
                            $group['produit'],
                            $group['quantite'],
                            number_format($group['prix_unitaire_ttc'], 2, ',', ''),
                            number_format($group['tva'], 1, ',', ''),
                            number_format($group['total_ht'], 2, ',', ''),
                            $group['paiement'],
                            number_format($group['total'], 2, ',', ''),
                        ]) . "\n";
                    }
                }

                if ($caisseData['modificationsGrouped']->isNotEmpty()) {
                    echo implode($sep, ['', 'Type', 'Qte', 'PF', 'P.U. HT', 'Paiement', 'Total TTC']) . "\n";
                    foreach ($caisseData['modificationsGrouped'] as $group) {
                        echo implode($sep, [
                            '',
                            $group['label'],
                            $group['quantite'],
                            $group['pf'] !== null ? number_format($group['pf'], 2, ',', '') : '',
                            $group['pu_ht'] !== null ? number_format($group['pu_ht'], 2, ',', '') : '',
                            $group['paiement'],
                            number_format($group['total'], 2, ',', ''),
                        ]) . "\n";
                    }
                }

                echo 'Total Caisse' . $sep . $sep . $sep . $sep . number_format($caisseTotalVentes + $caisseTotalMods, 2, ',', '') . "\n\n";
            }

            echo 'TOTAL GENERAL VENTES' . $sep . $sep . $sep . $sep . $sep . $sep . $sep . number_format($grandTotalVentes, 2, ',', '') . "\n";
            echo 'TOTAL GENERAL MODIFICATIONS' . $sep . $sep . $sep . $sep . $sep . $sep . $sep . number_format($grandTotalModifications, 2, ',', '') . "\n";
            echo 'TOTAL GENERAL' . $sep . $sep . $sep . $sep . $sep . $sep . $sep . number_format($grandTotalVentes + $grandTotalModifications, 2, ',', '') . "\n";

        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Concours $concours)
    {
        $clientsData = $this->getAllClientsData($concours);
        $caisseData = $this->getCaisseData($concours);

        return view('concours.factures.print', compact('concours', 'clientsData', 'caisseData'));
    }

    private function getAllClientsData(Concours $concours): array
    {
        $clients = ClientFacturation::whereHas('ventes', fn($q) => $q->where('concours_id', $concours->id))
            ->orWhereHas('modifications', fn($q) => $q->where('concours_id', $concours->id)->where('statut', '!=', 'supprime'))
            ->orderBy('nom')
            ->get();

        $result = [];
        foreach ($clients as $client) {
            [$ventes, $modifications, $totalVentes, $totalModifications] = $this->getClientData($concours, $client);
            $result[] = compact('client', 'ventes', 'modifications', 'totalVentes', 'totalModifications');
        }

        return $result;
    }

    private function getClientData(Concours $concours, ClientFacturation $client): array
    {
        $ventes = $client->ventes()
            ->where('concours_id', $concours->id)
            ->with('lignes.produit')
            ->latest()
            ->get();

        $modifications = $client->modifications()
            ->where('concours_id', $concours->id)
            ->where('statut', '!=', 'supprime')
            ->with(['engagement.epreuve', 'engagement.cavalier', 'engagement.cheval'])
            ->latest()
            ->get();

        $totalVentes = $ventes->sum('total_ttc');
        $totalModifications = $modifications->sum('prix');

        return [$ventes, $modifications, $totalVentes, $totalModifications];
    }

    private function getCaisseData(Concours $concours): array
    {
        // Ventes sans client facturation
        $caisseVentes = Vente::where('concours_id', $concours->id)
            ->whereNull('client_facturation_id')
            ->with('lignes.produit')
            ->get();

        // Modifications payantes sans client facturation
        $caisseModifications = Modification::where('concours_id', $concours->id)
            ->where('statut', '!=', 'supprime')
            ->whereIn('type', ['ajout_engagement', 'changement_epreuve'])
            ->whereNull('client_facturation_id')
            ->with(['engagement.epreuve', 'engagement.cavalier', 'engagement.cheval'])
            ->get();

        // Grouper les ventes par produit + mode de paiement
        $ventesFlat = collect();
        foreach ($caisseVentes as $vente) {
            $paiement = $this->getPaiementLabel($vente);
            foreach ($vente->lignes as $ligne) {
                $ventesFlat->push([
                    'produit' => $ligne->produit->nom,
                    'paiement' => $paiement,
                    'quantite' => $ligne->quantite,
                    'total' => (float) $ligne->total_ttc,
                    'prix_unitaire_ttc' => (float) $ligne->prix_unitaire_ttc,
                    'tva' => (float) $ligne->produit->tva,
                ]);
            }
        }

        $ventesGrouped = $ventesFlat->groupBy(fn($item) => $item['produit'] . '|' . $item['paiement'])
            ->map(function ($items, $key) {
                $first = $items->first();
                $totalTtc = $items->sum('total');
                $tva = $first['tva'];
                $totalHt = $this->calculateHtFromTtc($totalTtc, $tva);
                return [
                    'produit' => $first['produit'],
                    'paiement' => $first['paiement'],
                    'quantite' => $items->sum('quantite'),
                    'total' => $totalTtc,
                    'prix_unitaire_ttc' => $first['prix_unitaire_ttc'],
                    'tva' => $tva,
                    'total_ht' => $totalHt,
                ];
            })
            ->sortBy('produit')
            ->values();

        // Grouper les modifications par type + épreuve + mode de paiement + PF
        $modificationsGrouped = $caisseModifications->groupBy(function ($mod) {
            $paiement = $this->getPaiementLabel($mod);
            $epreuveNum = $mod->engagement->epreuve->numero ?? '?';
            $pf = $mod->pf !== null ? number_format($mod->pf, 2) : 'null';
            return $mod->type->value . '|' . $epreuveNum . '|' . $paiement . '|' . $pf;
        })->map(function ($items, $key) {
            $first = $items->first();
            $epreuveNum = $first->engagement->epreuve->numero ?? '?';
            $label = $first->type->label() . ' Ep.' . $epreuveNum;
            $paiement = $this->getPaiementLabel($first);
            $pf = $first->pf;
            $totalTtc = $items->sum('prix');
            $puHt = $this->calculateModificationHt((float) $first->prix, $pf);
            return [
                'label' => $label,
                'type' => $first->type->value,
                'epreuve_numero' => $epreuveNum,
                'paiement' => $paiement,
                'quantite' => $items->count(),
                'total' => $totalTtc,
                'pf' => $pf,
                'pu_ht' => $puHt,
            ];
        })->sortBy(['type', 'epreuve_numero'])
            ->values();

        $totalCaisseVentes = $caisseVentes->sum('total_ttc');
        $totalCaisseModifications = $caisseModifications->sum('prix');

        return compact(
            'caisseVentes', 'caisseModifications',
            'ventesGrouped', 'modificationsGrouped',
            'totalCaisseVentes', 'totalCaisseModifications'
        );
    }

}
