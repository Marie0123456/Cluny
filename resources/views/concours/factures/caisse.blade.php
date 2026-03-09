<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">Facture Caisse</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Retour -->
            <div class="mb-4">
                <a href="{{ route('concours.factures.index', $concours) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">&larr; Retour aux factures</a>
            </div>

            <!-- Header -->
            <div class="bg-amber-50 border border-amber-200 shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-amber-900 mb-1">CAISSE</h3>
                <p class="text-sm text-amber-700">Toutes les ventes et modifications payantes sans facturation nominative.</p>
            </div>

            <!-- Totaux -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Ventes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCaisseVentes, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Modifications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCaisseModifications, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Caisse</p>
                    <p class="text-2xl font-bold text-amber-900">{{ number_format($totalCaisseVentes + $totalCaisseModifications, 2, ',', ' ') }} &euro;</p>
                </div>
            </div>

            <!-- Ventes groupées -->
            @if ($ventesGrouped->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                        <p class="text-sm text-gray-500">Regroupées par produit et mode de paiement</p>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. TTC</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($ventesGrouped as $group)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $group['produit'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $group['quantite'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($group['prix_unitaire_ttc'], 2, ',', ' ') }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($group['tva'], 1) }}%</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($group['total_ht'], 2, ',', ' ') }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @foreach (explode(', ', $group['paiement']) as $p)
                                                @if ($p === 'CB')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>
                                                @elseif ($p === 'Espèces')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Espèces</span>
                                                @elseif ($p === 'Chèque')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Chèque</span>
                                                @else
                                                    <span class="text-gray-400">{{ $p }}</span>
                                                @endif
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ number_format($group['total'], 2, ',', ' ') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total ventes</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalCaisseVentes, 2, ',', ' ') }} &euro;</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Modifications groupées -->
            @if ($modificationsGrouped->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Modifications payantes</h3>
                        <p class="text-sm text-gray-500">Regroupées par type, épreuve et mode de paiement</p>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. HT</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($modificationsGrouped as $group)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $group['label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $group['quantite'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $group['pf'] !== null ? number_format($group['pf'], 2, ',', ' ') . ' €' : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $group['pu_ht'] !== null ? number_format($group['pu_ht'], 2, ',', ' ') . ' €' : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @foreach (explode(', ', $group['paiement']) as $p)
                                                @if ($p === 'CB')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>
                                                @elseif ($p === 'Espèces')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Espèces</span>
                                                @elseif ($p === 'Chèque')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Chèque</span>
                                                @else
                                                    <span class="text-gray-400">{{ $p }}</span>
                                                @endif
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ number_format($group['total'], 2, ',', ' ') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total modifications</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalCaisseModifications, 2, ',', ' ') }} &euro;</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
