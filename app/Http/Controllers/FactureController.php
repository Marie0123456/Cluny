<?php

namespace App\Http\Controllers;

use App\Models\ClientFacturation;
use App\Models\Concours;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FactureController extends Controller
{
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
            ->orderBy('nom')
            ->get();

        return view('concours.factures.index', compact('concours', 'clients'));
    }

    public function show(Concours $concours, ClientFacturation $client)
    {
        [$ventes, $modifications, $totalVentes, $totalModifications] = $this->getClientData($concours, $client);

        return view('concours.factures.show', compact('concours', 'client', 'ventes', 'modifications', 'totalVentes', 'totalModifications'));
    }

    public function exportCsv(Concours $concours): StreamedResponse
    {
        $clientsData = $this->getAllClientsData($concours);
        $filename = 'factures_' . str_replace(' ', '_', $concours->nom) . '_' . $concours->date_debut->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($concours, $clientsData) {
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
                        $paiements = [];
                        if ($vente->paiement_cb) $paiements[] = 'CB';
                        if ($vente->paiement_especes) $paiements[] = 'Especes';
                        if ($vente->paiement_cheque) $paiements[] = 'Cheque';
                        $paiementStr = implode(', ', $paiements);
                        $dateStr = $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '';

                        foreach ($vente->lignes as $index => $ligne) {
                            $totalHt = round($ligne->total_ttc / (1 + $ligne->produit->tva / 100), 2);
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
                    echo implode($sep, ['Nom facturation', 'N. Epreuve', 'Cavalier', 'Cheval', 'Type', 'PF', 'P.U. HT', 'Prix TTC', 'Paiement', 'Date']) . "\n";
                    foreach ($modifications as $index => $mod) {
                        $paiements = [];
                        if ($mod->paiement_cb) $paiements[] = 'CB';
                        if ($mod->paiement_especes) $paiements[] = 'Especes';
                        if ($mod->paiement_cheque) $paiements[] = 'Cheque';

                        $puHt = ($mod->prix && $mod->pf !== null) ? round(($mod->prix - $mod->pf) / 1.055, 2) : '';

                        echo implode($sep, [
                            $index === 0 ? $client->nom : '',
                            $mod->engagement->epreuve->numero ?? '-',
                            trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                            $mod->engagement->cheval->nom ?? '-',
                            $mod->type->label(),
                            $mod->pf !== null ? number_format($mod->pf, 2, ',', '') : '',
                            $puHt !== '' ? number_format($puHt, 2, ',', '') : '',
                            $mod->prix ? number_format($mod->prix, 2, ',', '') : '',
                            implode(', ', $paiements),
                            $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '',
                        ]) . "\n";
                    }
                }

                echo 'Total ' . $client->nom . $sep . $sep . $sep . $sep . $sep . $sep . $sep . number_format($totalVentes + $totalModifications, 2, ',', '') . "\n";
                echo "\n";
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

        return view('concours.factures.print', compact('concours', 'clientsData'));
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
}
