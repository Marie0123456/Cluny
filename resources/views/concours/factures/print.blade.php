<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Factures - {{ $concours->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin: 24px 0 8px; background: #4f46e5; color: white; padding: 6px 10px; border-radius: 4px; }
        h3 { font-size: 13px; margin: 12px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; color: #555; }
        .header { margin-bottom: 20px; }
        .header .subtitle { color: #666; font-size: 13px; }
        .client-details { color: #666; font-size: 11px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #f3f4f6; text-align: left; padding: 5px 6px; font-size: 10px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e5e7eb; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .client-total { background: #f9fafb; font-weight: bold; font-size: 12px; }
        .client-total td { padding: 6px; border-top: 2px solid #e5e7eb; }
        .grand-total { margin-top: 30px; border: 2px solid #4f46e5; border-radius: 4px; padding: 12px; text-align: right; }
        .grand-total .line { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 13px; }
        .grand-total .line.main { font-size: 16px; font-weight: bold; color: #4f46e5; margin-top: 6px; padding-top: 6px; border-top: 1px solid #e5e7eb; }
        .no-print { margin-top: 20px; text-align: center; }
        .no-print button { padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; }
        .no-print button:hover { background: #4338ca; }
        .page-break { page-break-before: always; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
        @page { margin: 12mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Factures - {{ $concours->nom }}</h1>
        <div class="subtitle">{{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}</div>
    </div>

    @php
        $grandTotalVentes = 0;
        $grandTotalModifications = 0;
    @endphp

    @foreach ($clientsData as $data)
        @php
            $client = $data['client'];
            $ventes = $data['ventes'];
            $modifications = $data['modifications'];
            $totalVentes = $data['totalVentes'];
            $totalModifications = $data['totalModifications'];
            $grandTotalVentes += $totalVentes;
            $grandTotalModifications += $totalModifications;
        @endphp

        <h2>{{ $client->nom }}</h2>
        <div class="client-details">
            @if ($client->telephone) Tel : {{ $client->telephone }} &nbsp;|&nbsp; @endif
            @if ($client->email) Email : {{ $client->email }} &nbsp;|&nbsp; @endif
            @if ($client->adresse) Adresse : {{ $client->adresse }} @endif
        </div>
        @php $commentaireFacture = \App\Models\FactureCommentaire::where('concours_id', $concours->id)->where('client_facturation_id', $client->id)->first(); @endphp
        @if ($commentaireFacture)
            <div style="font-size: 11px; color: #92400e; background: #fffbeb; padding: 4px 8px; border-radius: 3px; margin-bottom: 8px;">
                {{ $commentaireFacture->commentaire }}
            </div>
        @endif

        @if ($ventes->isNotEmpty())
            <h3>Ventes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="text-center">Qte</th>
                        <th class="text-right">P.U. TTC</th>
                        <th class="text-right">TVA</th>
                        <th class="text-right">Total HT</th>
                        <th class="text-right">Total TTC</th>
                        <th>Paiement</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventes as $vente)
                        @foreach ($vente->lignes as $index => $ligne)
                            <tr>
                                @if ($index === 0)
                                    <td rowspan="{{ $vente->lignes->count() }}" class="font-bold">
                                        {{ $vente->nom_client }}
                                        @if ($vente->commentaire)
                                            <div style="font-weight: normal; font-size: 0.85em; color: #92400e; margin-top: 2px;">{{ $vente->commentaire }}</div>
                                        @endif
                                    </td>
                                @endif
                                <td>{{ $ligne->produit->nom }}</td>
                                <td class="text-center">{{ $ligne->quantite }}</td>
                                <td class="text-right">{{ number_format($ligne->prix_unitaire_ttc, 2, ',', ' ') }} &euro;</td>
                                <td class="text-right">{{ number_format($ligne->produit->tva, 1) }}%</td>
                                @php $totalHt = round($ligne->total_ttc / (1 + $ligne->produit->tva / 100), 2); @endphp
                                <td class="text-right">{{ number_format($totalHt, 2, ',', ' ') }} &euro;</td>
                                <td class="text-right">{{ number_format($ligne->total_ttc, 2, ',', ' ') }} &euro;</td>
                                @if ($index === 0)
                                    <td rowspan="{{ $vente->lignes->count() }}">
                                        @if ($vente->paiement_cb) CB @endif
                                        @if ($vente->paiement_especes) Espèces @endif
                                        @if ($vente->paiement_cheque) Chèque @endif
                                        @if ($vente->paiement_internet) Internet @endif
                                        @if ($vente->paiement_virement) Virement @endif
                                    </td>
                                    <td rowspan="{{ $vente->lignes->count() }}">
                                        {{ $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '-' }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($modifications->isNotEmpty())
            <h3>Modifications</h3>
            <table>
                <thead>
                    <tr>
                        <th>N. Epr.</th>
                        <th>Cavalier</th>
                        <th>Cheval</th>
                        <th>Type</th>
                        <th class="text-right">PF</th>
                        <th class="text-right">P.U. HT</th>
                        <th class="text-right">Prix</th>
                        <th>Paiement</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modifications as $mod)
                        <tr>
                            <td class="font-bold">{{ $mod->engagement->epreuve->numero ?? '-' }}</td>
                            <td>{{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}</td>
                            <td>{{ $mod->engagement->cheval->nom ?? '-' }}</td>
                            <td>{{ $mod->type->label() }}</td>
                            <td class="text-right">{{ $mod->pf !== null ? number_format($mod->pf, 2, ',', ' ') . ' €' : '-' }}</td>
                            <td class="text-right">
                                @if ($mod->prix && $mod->pf !== null)
                                    {{ number_format(($mod->prix - $mod->pf) / (1 + config('ehnc.tva_modifications') / 100), 2, ',', ' ') }} &euro;
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right font-bold">{{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}</td>
                            <td>
                                @if ($mod->paiement_cb) CB @endif
                                @if ($mod->paiement_especes) Espèces @endif
                                @if ($mod->paiement_cheque) Chèque @endif
                                @if ($mod->paiement_internet) Internet @endif
                                @if ($mod->paiement_virement) Virement @endif
                            </td>
                            <td>{{ $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table>
            <tr class="client-total">
                <td colspan="6" class="text-right">Total {{ $client->nom }}</td>
                <td class="text-right">{{ number_format($totalVentes + $totalModifications, 2, ',', ' ') }} &euro;</td>
                <td></td>
            </tr>
        </table>
    @endforeach

    {{-- Caisse --}}
    @php
        $caisseTotalVentes = $caisseData['totalCaisseVentes'];
        $caisseTotalMods = $caisseData['totalCaisseModifications'];
        $grandTotalVentes += $caisseTotalVentes;
        $grandTotalModifications += $caisseTotalMods;
    @endphp

    @if ($caisseTotalVentes > 0 || $caisseTotalMods > 0)
        <h2>CAISSE</h2>
        <div class="client-details">Ventes et modifications sans facturation nominative</div>

        @if ($caisseData['ventesGrouped']->isNotEmpty())
            <h3>Ventes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-center">Qte</th>
                        <th class="text-right">P.U. TTC</th>
                        <th class="text-right">TVA</th>
                        <th class="text-right">Total HT</th>
                        <th>Paiement</th>
                        <th class="text-right">Total TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($caisseData['ventesGrouped'] as $group)
                        <tr>
                            <td class="font-bold">{{ $group['produit'] }}</td>
                            <td class="text-center">{{ $group['quantite'] }}</td>
                            <td class="text-right">{{ number_format($group['prix_unitaire_ttc'], 2, ',', ' ') }} &euro;</td>
                            <td class="text-right">{{ number_format($group['tva'], 1) }}%</td>
                            <td class="text-right">{{ number_format($group['total_ht'], 2, ',', ' ') }} &euro;</td>
                            <td>{{ $group['paiement'] }}</td>
                            <td class="text-right font-bold">{{ number_format($group['total'], 2, ',', ' ') }} &euro;</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($caisseData['modificationsGrouped']->isNotEmpty())
            <h3>Modifications</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="text-center">Qte</th>
                        <th class="text-right">PF</th>
                        <th class="text-right">P.U. HT</th>
                        <th>Paiement</th>
                        <th class="text-right">Total TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($caisseData['modificationsGrouped'] as $group)
                        <tr>
                            <td class="font-bold">{{ $group['label'] }}</td>
                            <td class="text-center">{{ $group['quantite'] }}</td>
                            <td class="text-right">{{ $group['pf'] !== null ? number_format($group['pf'], 2, ',', ' ') . ' €' : '-' }}</td>
                            <td class="text-right">{{ $group['pu_ht'] !== null ? number_format($group['pu_ht'], 2, ',', ' ') . ' €' : '-' }}</td>
                            <td>{{ $group['paiement'] }}</td>
                            <td class="text-right font-bold">{{ number_format($group['total'], 2, ',', ' ') }} &euro;</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table>
            <tr class="client-total">
                <td colspan="5" class="text-right">Total Caisse</td>
                <td class="text-right">{{ number_format($caisseTotalVentes + $caisseTotalMods, 2, ',', ' ') }} &euro;</td>
            </tr>
        </table>
    @endif

    <div class="grand-total">
        <div class="line"><span>Total Ventes</span> <span>{{ number_format($grandTotalVentes, 2, ',', ' ') }} &euro;</span></div>
        <div class="line"><span>Total Modifications</span> <span>{{ number_format($grandTotalModifications, 2, ',', ' ') }} &euro;</span></div>
        <div class="line main"><span>TOTAL GÉNÉRAL</span> <span>{{ number_format($grandTotalVentes + $grandTotalModifications, 2, ',', ' ') }} &euro;</span></div>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
