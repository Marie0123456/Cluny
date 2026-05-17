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
                                                @elseif ($p === 'Internet')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Internet</span>
                                                @elseif ($p === 'Virement')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Virement</span>
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
                @php
                    $modsGroupedData = $modificationsGrouped->map(fn($g) => [
                        'label'     => $g['label'],
                        'quantite'  => (int) $g['quantite'],
                        'pf'        => $g['pf'] !== null ? (float) $g['pf'] : null,
                        'pu_ht'     => $g['pu_ht'] !== null ? (float) $g['pu_ht'] : null,
                        'paiements' => array_filter(explode(', ', $g['paiement']), fn($p) => $p !== ''),
                        'total'     => (float) $g['total'],
                    ])->values()->toArray();

                    $distinctPf   = $modificationsGrouped->map(fn($g) => $g['pf'] !== null ? (float) $g['pf'] : null)->filter(fn($v) => $v !== null)->unique()->sort()->values();
                    $distinctPuHt = $modificationsGrouped->map(fn($g) => $g['pu_ht'] !== null ? (float) $g['pu_ht'] : null)->filter(fn($v) => $v !== null)->unique()->sort()->values();
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg"
                     x-data="{
                         groups: @js($modsGroupedData),
                         filterPf: '',
                         filterPuHt: '',
                         filterPaiement: '',
                         get filteredGroups() {
                             return this.groups.filter(g => {
                                 if (this.filterPf !== '' && parseFloat(g.pf).toFixed(2) !== parseFloat(this.filterPf).toFixed(2)) return false;
                                 if (this.filterPuHt !== '' && parseFloat(g.pu_ht).toFixed(2) !== parseFloat(this.filterPuHt).toFixed(2)) return false;
                                 if (this.filterPaiement !== '' && !g.paiements.includes(this.filterPaiement)) return false;
                                 return true;
                             });
                         },
                         get totalQuantite() { return this.filteredGroups.reduce((s, g) => s + g.quantite, 0); },
                         get totalPF()  { return this.filteredGroups.reduce((s, g) => s + (g.pf   ?? 0) * g.quantite, 0); },
                         get totalPuHt(){ return this.filteredGroups.reduce((s, g) => s + (g.pu_ht ?? 0) * g.quantite, 0); },
                         get totalTtc() { return this.filteredGroups.reduce((s, g) => s + g.total, 0); },
                         fmt(n) { return n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                         paiementClass(p) {
                             const map = {
                                 'CB':       'bg-blue-100 text-blue-800',
                                 'Espèces':  'bg-green-100 text-green-800',
                                 'Chèque':   'bg-yellow-100 text-yellow-800',
                                 'Internet': 'bg-purple-100 text-purple-800',
                                 'Virement': 'bg-indigo-100 text-indigo-800',
                             };
                             return 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' + (map[p] ?? 'bg-gray-100 text-gray-600');
                         },
                     }">
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
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        <div class="flex flex-col items-end gap-1">
                                            <span>PF</span>
                                            <select x-model="filterPf" class="text-xs font-normal normal-case border border-gray-300 rounded px-1 py-0.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Tous</option>
                                                @foreach ($distinctPf as $val)
                                                    <option value="{{ $val }}">{{ number_format($val, 2, ',', ' ') }} €</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        <div class="flex flex-col items-end gap-1">
                                            <span>P.U. HT</span>
                                            <select x-model="filterPuHt" class="text-xs font-normal normal-case border border-gray-300 rounded px-1 py-0.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Tous</option>
                                                @foreach ($distinctPuHt as $val)
                                                    <option value="{{ $val }}">{{ number_format($val, 2, ',', ' ') }} €</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div class="flex flex-col items-start gap-1">
                                            <span>Paiement</span>
                                            <select x-model="filterPaiement" class="text-xs font-normal normal-case border border-gray-300 rounded px-1 py-0.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Tous</option>
                                                <option value="CB">CB</option>
                                                <option value="Espèces">Espèces</option>
                                                <option value="Chèque">Chèque</option>
                                                <option value="Internet">Internet</option>
                                                <option value="Virement">Virement</option>
                                            </select>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(g, i) in filteredGroups" :key="i">
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="g.label"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-center" x-text="g.quantite"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="g.pf !== null ? fmt(g.pf) + ' €' : '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="g.pu_ht !== null ? fmt(g.pu_ht) + ' €' : '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <template x-for="p in g.paiements" :key="p">
                                                <span :class="paiementClass(p)" x-text="p"></span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right" x-text="fmt(g.total) + ' €'"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredGroups.length === 0">
                                    <td colspan="6" class="px-4 py-6 text-sm text-gray-400 text-center">Aucun résultat pour ces filtres.</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total quantité</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-center" x-text="totalQuantite"></td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-right"><span x-text="fmt(totalPF)"></span> &euro;</td>
                                    <td colspan="3" class="px-4 py-2"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total P.U. HT</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-right"><span x-text="fmt(totalPuHt)"></span> &euro;</td>
                                    <td colspan="2" class="px-4 py-2"></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total modifications TTC</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right"><span x-text="fmt(totalTtc)"></span> &euro;</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
