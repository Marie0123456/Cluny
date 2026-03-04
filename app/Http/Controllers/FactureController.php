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

    public function exportCsv(Concours $concours, ClientFacturation $client): StreamedResponse
    {
        [$ventes, $modifications, $totalVentes, $totalModifications] = $this->getClientData($concours, $client);

        $filename = 'facture_' . str_replace(' ', '_', $client->nom) . '_' . $concours->date_debut->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($client, $concours, $ventes, $modifications, $totalVentes, $totalModifications) {
            // UTF-8 BOM for Excel
            echo "\xEF\xBB\xBF";

            $sep = ';';

            // Client info
            echo 'Facture - ' . $concours->nom . "\n";
            echo 'Client;' . $client->nom . "\n";
            echo 'Telephone;' . ($client->telephone ?? '') . "\n";
            echo 'Email;' . ($client->email ?? '') . "\n";
            echo 'Adresse;' . ($client->adresse ?? '') . "\n";
            echo "\n";

            // Ventes
            if ($ventes->isNotEmpty()) {
                echo "VENTES\n";
                echo implode($sep, ['Client', 'Produit', 'Qte', 'P.U. TTC', 'TVA %', 'Total HT', 'Total TTC', 'Paiement']) . "\n";
                foreach ($ventes as $vente) {
                    $paiements = [];
                    if ($vente->paiement_cb) $paiements[] = 'CB';
                    if ($vente->paiement_especes) $paiements[] = 'Especes';
                    if ($vente->paiement_cheque) $paiements[] = 'Cheque';
                    $paiementStr = implode(', ', $paiements);

                    foreach ($vente->lignes as $index => $ligne) {
                        $totalHt = round($ligne->total_ttc / (1 + $ligne->produit->tva / 100), 2);
                        echo implode($sep, [
                            $index === 0 ? $vente->nom_client : '',
                            $ligne->produit->nom,
                            $ligne->quantite,
                            number_format($ligne->prix_unitaire_ttc, 2, ',', ''),
                            number_format($ligne->produit->tva, 1, ',', ''),
                            number_format($totalHt, 2, ',', ''),
                            number_format($ligne->total_ttc, 2, ',', ''),
                            $index === 0 ? $paiementStr : '',
                        ]) . "\n";
                    }
                }
                echo implode($sep, ['', '', '', '', '', 'Sous-total ventes', number_format($totalVentes, 2, ',', ''), '']) . "\n";
                echo "\n";
            }

            // Modifications
            if ($modifications->isNotEmpty()) {
                echo "MODIFICATIONS\n";
                echo implode($sep, ['N. Epreuve', 'Cavalier', 'Cheval', 'Type', 'PF', 'P.U. HT', 'Prix TTC', 'Paiement']) . "\n";
                foreach ($modifications as $mod) {
                    $paiements = [];
                    if ($mod->paiement_cb) $paiements[] = 'CB';
                    if ($mod->paiement_especes) $paiements[] = 'Especes';
                    if ($mod->paiement_cheque) $paiements[] = 'Cheque';

                    $puHt = ($mod->prix && $mod->pf !== null) ? round(($mod->prix - $mod->pf) / 1.055, 2) : '';

                    echo implode($sep, [
                        $mod->engagement->epreuve->numero ?? '-',
                        trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                        $mod->engagement->cheval->nom ?? '-',
                        $mod->type->label(),
                        $mod->pf !== null ? number_format($mod->pf, 2, ',', '') : '',
                        $puHt !== '' ? number_format($puHt, 2, ',', '') : '',
                        $mod->prix ? number_format($mod->prix, 2, ',', '') : '',
                        implode(', ', $paiements),
                    ]) . "\n";
                }
                echo implode($sep, ['', '', '', '', '', 'Sous-total modifications', number_format($totalModifications, 2, ',', ''), '']) . "\n";
                echo "\n";
            }

            // Total general
            echo implode($sep, ['', '', '', '', '', 'TOTAL GENERAL', number_format($totalVentes + $totalModifications, 2, ',', ''), '']) . "\n";

        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Concours $concours, ClientFacturation $client)
    {
        [$ventes, $modifications, $totalVentes, $totalModifications] = $this->getClientData($concours, $client);

        return view('concours.factures.print', compact('concours', 'client', 'ventes', 'modifications', 'totalVentes', 'totalModifications'));
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
