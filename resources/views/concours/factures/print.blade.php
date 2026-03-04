<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture - {{ $client->nom }} - {{ $concours->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 20px 0 8px; border-bottom: 2px solid #4f46e5; padding-bottom: 4px; }
        .header { margin-bottom: 20px; }
        .header .subtitle { color: #666; font-size: 13px; }
        .client-info { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px; margin-bottom: 20px; }
        .client-info .name { font-size: 16px; font-weight: bold; margin-bottom: 6px; }
        .client-info .detail { color: #666; margin-bottom: 2px; }
        .totals { display: flex; gap: 16px; margin-bottom: 20px; }
        .total-box { flex: 1; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; text-align: center; }
        .total-box .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .total-box .value { font-size: 18px; font-weight: bold; margin-top: 2px; }
        .total-box.main { border-color: #4f46e5; }
        .total-box.main .value { color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e5e7eb; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        tfoot td { background: #f9fafb; font-weight: bold; }
        .no-print { margin-top: 20px; text-align: center; }
        .no-print button { padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; }
        .no-print button:hover { background: #4338ca; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
        @page { margin: 15mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $concours->nom }}</h1>
        <div class="subtitle">{{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}</div>
    </div>

    <div class="client-info">
        <div class="name">{{ $client->nom }}</div>
        @if ($client->telephone)<div class="detail">Tel : {{ $client->telephone }}</div>@endif
        @if ($client->email)<div class="detail">Email : {{ $client->email }}</div>@endif
        @if ($client->adresse)<div class="detail">Adresse : {{ $client->adresse }}</div>@endif
    </div>

    <div class="totals">
        <div class="total-box">
            <div class="label">Total Ventes</div>
            <div class="value">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</div>
        </div>
        <div class="total-box">
            <div class="label">Total Modifications</div>
            <div class="value">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</div>
        </div>
        <div class="total-box main">
            <div class="label">Total General</div>
            <div class="value">{{ number_format($totalVentes + $totalModifications, 2, ',', ' ') }} &euro;</div>
        </div>
    </div>

    @if ($ventes->isNotEmpty())
        <h2>Ventes</h2>
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
                </tr>
            </thead>
            <tbody>
                @foreach ($ventes as $vente)
                    @foreach ($vente->lignes as $index => $ligne)
                        <tr>
                            @if ($index === 0)
                                <td rowspan="{{ $vente->lignes->count() }}" class="font-bold">{{ $vente->nom_client }}</td>
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
                                    @if ($vente->paiement_especes) Especes @endif
                                    @if ($vente->paiement_cheque) Cheque @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">Sous-total ventes</td>
                    <td class="text-right">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    @if ($modifications->isNotEmpty())
        <h2>Modifications payantes</h2>
        <table>
            <thead>
                <tr>
                    <th>N. Epreuve</th>
                    <th>Cavalier</th>
                    <th>Cheval</th>
                    <th>Type</th>
                    <th class="text-right">PF</th>
                    <th class="text-right">P.U. HT</th>
                    <th class="text-right">Prix</th>
                    <th>Paiement</th>
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
                                {{ number_format(($mod->prix - $mod->pf) / 1.055, 2, ',', ' ') }} &euro;
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-bold">{{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}</td>
                        <td>
                            @if ($mod->paiement_cb) CB @endif
                            @if ($mod->paiement_especes) Especes @endif
                            @if ($mod->paiement_cheque) Cheque @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">Sous-total modifications</td>
                    <td class="text-right">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
