<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Facture : {{ $client->nom }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Retour -->
            <div class="mb-4">
                <a href="{{ route('concours.factures.index', $concours) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">&larr; Retour aux factures</a>
            </div>

            <!-- Infos client -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">{{ $client->nom }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Telephone :</span>
                        <span class="text-gray-900 ml-1">{{ $client->telephone ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Email :</span>
                        <span class="text-gray-900 ml-1">{{ $client->email ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Adresse :</span>
                        <span class="text-gray-900 ml-1">{{ $client->adresse ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Totaux -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Ventes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Modifications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total General</p>
                    <p class="text-2xl font-bold text-indigo-900">{{ number_format($totalVentes + $totalModifications, 2, ',', ' ') }} &euro;</p>
                </div>
            </div>

            <!-- Ventes -->
            @if ($ventes->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qte</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. TTC</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($ventes as $vente)
                                    @php $ligneCount = $vente->lignes->count(); @endphp
                                    @foreach ($vente->lignes as $index => $ligne)
                                        <tr class="{{ $index === 0 ? 'border-t-2 border-gray-300' : '' }}">
                                            @if ($index === 0)
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900" rowspan="{{ $ligneCount }}">{{ $vente->nom_client }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $ligne->produit->nom }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $ligne->quantite }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($ligne->prix_unitaire_ttc, 2, ',', ' ') }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($ligne->produit->tva, 1) }}%</td>
                                            @php
                                                $totalHt = round($ligne->total_ttc / (1 + $ligne->produit->tva / 100), 2);
                                            @endphp
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($totalHt, 2, ',', ' ') }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right">{{ number_format($ligne->total_ttc, 2, ',', ' ') }} &euro;</td>
                                            @if ($index === 0)
                                                <td class="px-4 py-3 text-sm text-gray-500" rowspan="{{ $ligneCount }}">
                                                    @if ($vente->paiement_cb)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>@endif
                                                    @if ($vente->paiement_especes)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Especes</span>@endif
                                                    @if ($vente->paiement_cheque)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Cheque</span>@endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total ventes</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Modifications -->
            @if ($modifications->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Modifications payantes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N. Epreuve</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($modifications as $mod)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $mod->engagement->epreuve->numero ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $mod->engagement->cheval->nom ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                            {{ $mod->pf ? number_format($mod->pf, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                            @if ($mod->prix && $mod->pf !== null)
                                                {{ number_format(($mod->prix - $mod->pf) / 1.055, 2, ',', ' ') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right">
                                            {{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @php
                                                $paiements = [];
                                                if ($mod->paiement_cb) $paiements[] = 'CB';
                                                if ($mod->paiement_especes) $paiements[] = 'Especes';
                                                if ($mod->paiement_cheque) $paiements[] = 'Cheque';
                                            @endphp
                                            {{ $paiements ? implode(', ', $paiements) : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total modifications</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
