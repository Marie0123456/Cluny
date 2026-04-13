<?php

namespace App\Http\Traits;

use App\Models\ClientFacturation;

trait HandlesPaiement
{
    protected function getPaiementLabel($item, string $empty = 'Non renseigné'): string
    {
        $paiements = [];
        if ($item->paiement_cb) $paiements[] = 'CB';
        if ($item->paiement_especes) $paiements[] = 'Espèces';
        if ($item->paiement_cheque) $paiements[] = 'Chèque';
        if ($item->paiement_internet) $paiements[] = 'Internet';
        if ($item->paiement_virement) $paiements[] = 'Virement';
        return $paiements ? implode(', ', $paiements) : $empty;
    }

    protected function resolveClientFacturation(array $validated, bool $facture): ?int
    {
        if ($facture && !empty($validated['nom_facturation'])) {
            $client = ClientFacturation::updateOrCreateByNom(
                $validated['nom_facturation'],
                [
                    'telephone' => $validated['telephone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'adresse' => $validated['adresse'] ?? null,
                ]
            );
            return $client->id;
        }

        return null;
    }

    protected function calculateHtFromTtc(float $ttc, float $tvaPercent): float
    {
        return round($ttc / (1 + $tvaPercent / 100), 2);
    }

    protected function calculateModificationHt(float $prix, ?float $pf, ?float $tvaPercent = null): ?float
    {
        $tvaPercent ??= config('ehnc.tva_modifications');
        if ($prix && $pf !== null) {
            return round(($prix - $pf) / (1 + $tvaPercent / 100), 2);
        }
        return null;
    }
}
