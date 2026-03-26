<?php

namespace App\Http\Controllers;

use App\Enums\ModificationType;
use App\Http\Traits\HandlesPaiement;
use App\Models\Concours;

class FacturationEtController extends Controller
{
    use HandlesPaiement;
    private function getModifications(Concours $concours)
    {
        return $concours->modifications()
            ->whereIn('type', [
                ModificationType::AJOUT_ENGAGEMENT->value,
                ModificationType::CHANGEMENT_EPREUVE->value,
            ])
            ->where('statut', '!=', 'supprime')
            ->with([
                'engagement.epreuve',
                'engagement.cavalier',
                'engagement.cheval',
                'clientFacturation',
            ])
            ->latest()
            ->get();
    }

    public function index(Concours $concours)
    {
        $modifications = $this->getModifications($concours);

        $totalPrix = $modifications->sum('prix');
        $totalPf = $modifications->sum('pf');

        $caisseData = $modifications->map(fn ($m) => [
            'jour' => $m->jour_paiement?->format('Y-m-d'),
            'total' => (float) $m->prix,
            'cb' => (bool) $m->paiement_cb,
            'especes' => (bool) $m->paiement_especes,
            'cheque' => (bool) $m->paiement_cheque,
            'internet' => (bool) $m->paiement_internet,
            'virement' => (bool) $m->paiement_virement,
        ]);

        return view('concours.facturation-et.index', compact(
            'concours', 'modifications', 'totalPrix', 'totalPf', 'caisseData'
        ));
    }

    public function exportCsv(Concours $concours)
    {
        $modifications = $this->getModifications($concours);

        $filename = 'facturation_et_' . str_replace(' ', '_', $concours->nom) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($modifications) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'N. Épreuve', 'Nom Épreuve', 'Cavalier', 'Cheval', 'Type de modif',
                'PF', 'PU HT', 'Prix TTC',
                'Paiement', 'N° Cheque', 'Jour paiement',
                'Facture', 'Nom facturation', 'Telephone', 'Email', 'Adresse',
            ], ';');

            foreach ($modifications as $mod) {
                $prix = (float) $mod->prix;
                $pf = (float) $mod->pf;
                $puHt = $prix > 0 ? $this->calculateModificationHt($prix, $pf) : 0;

                fputcsv($handle, [
                    $mod->engagement->epreuve->numero ?? '-',
                    $mod->engagement->epreuve->nom ?? '-',
                    trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                    $mod->engagement->cheval->nom ?? '-',
                    $mod->type->label(),
                    $mod->pf ? number_format((float) $mod->pf, 2, ',', '') : '',
                    $puHt > 0 ? number_format($puHt, 2, ',', '') : '',
                    $prix > 0 ? number_format($prix, 2, ',', '') : '',
                    $this->getPaiementLabel($mod),
                    $mod->numero_cheque ?? '',
                    $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '',
                    $mod->facture ? 'Oui' : 'Non',
                    $mod->clientFacturation->nom ?? '',
                    $mod->clientFacturation->telephone ?? '',
                    $mod->clientFacturation->email ?? '',
                    $mod->clientFacturation->adresse ?? '',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
