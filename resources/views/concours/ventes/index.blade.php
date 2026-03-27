<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @include('concours.partials.tabs', ['active' => 'ventes'])

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
                <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                <div class="flex flex-wrap gap-3">
                    @if ($ventes->isNotEmpty())
                        <a href="{{ route('concours.ventes.export-csv', $concours) }}"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Export CSV
                        </a>
                    @endif
                    <a href="{{ route('concours.ventes.create', $concours) }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        + Nouvelle vente
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg" x-data="ventesFilter()" x-cloak>
                @if ($ventes->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune vente pour le moment.
                    </div>
                @else
                    <!-- Filtres -->
                    <div class="px-4 pt-4 pb-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Client</label>
                            <input type="text" x-model="filterClient" placeholder="Nom du client..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Produit</label>
                            <select x-model="filterProduit"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                @php
                                    $produitNames = $ventes->flatMap(fn($v) => $v->lignes->pluck('produit.nom'))->unique()->sort();
                                @endphp
                                @foreach ($produitNames as $pnom)
                                    <option value="{{ $pnom }}">{{ $pnom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Facturation</label>
                            <select x-model="filterFacture"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                <option value="oui">Avec facture</option>
                                <option value="non">Sans facture</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nom facturation</label>
                            <input type="text" x-model="filterNomFacturation" placeholder="Nom de facturation..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                    <div x-show="filterClient || filterProduit || filterFacture || filterNomFacturation" class="px-4 pb-2">
                        <button @click="resetFilters()" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Réinitialiser les filtres</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qte</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. TTC</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($ventes as $vente)
                                    @php $ligneCount = $vente->lignes->count() ?: 1; @endphp
                                    @foreach ($vente->lignes as $ligne)
                                        <tr x-show="showRow({{ json_encode([
                                            'client' => $vente->nom_client,
                                            'produits' => $vente->lignes->pluck('produit.nom')->join(', '),
                                            'facture' => $vente->facture,
                                            'nom_facturation' => $vente->clientFacturation->nom ?? '',
                                        ]) }})" class="{{ $loop->first ? 'border-t-2 border-gray-300' : '' }}">
                                            @if ($loop->first)
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900" rowspan="{{ $ligneCount }}">
                                                    <span class="cursor-help" title="Créé par {{ $vente->createdByUser->name ?? 'inconnu' }} le {{ $vente->created_at->format('d/m/Y à H:i') }}{{ $vente->modifiedByUser ? ' — Modifié par ' . $vente->modifiedByUser->name . ' le ' . $vente->updated_at->format('d/m/Y à H:i') : '' }}">{{ $vente->nom_client }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-500" rowspan="{{ $ligneCount }}">{{ $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '-' }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $ligne->produit->nom }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $ligne->quantite }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ number_format($ligne->prix_unitaire_ttc, 2, ',', ' ') }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ number_format($ligne->produit->tva, 1) }}%</td>
                                            @if ($loop->first)
                                                <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right" rowspan="{{ $ligneCount }}">{{ number_format($vente->total_ttc, 2, ',', ' ') }} &euro;</td>
                                                <td class="px-4 py-3 text-sm text-gray-500" rowspan="{{ $ligneCount }}">
                                                    @if ($vente->paiement_cb)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>@endif
                                                    @if ($vente->paiement_especes)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Espèces</span>@endif
                                                    @if ($vente->paiement_cheque)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Chèque</span>@endif
                                                    @if ($vente->paiement_internet)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Internet</span>@endif
                                                    @if ($vente->paiement_virement)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Virement</span>@endif
                                                </td>
                                                <td class="px-4 py-3 text-sm" rowspan="{{ $ligneCount }}">
                                                    @if ($vente->facture && $vente->clientFacturation)
                                                        <a href="{{ route('concours.factures.show', [$concours, $vente->clientFacturation]) }}"
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition">
                                                            {{ $vente->clientFacturation->nom }}
                                                        </a>
                                                    @elseif ($vente->facture)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Oui</span>
                                                    @else
                                                        <span class="text-gray-400">Non</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right space-x-2" rowspan="{{ $ligneCount }}">
                                                    <a href="{{ route('ventes.edit', $vente) }}" class="text-amber-600 hover:text-amber-900 text-xs font-medium">Modifier</a>
                                                    <a href="{{ route('ventes.show', $vente) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Voir</a>
                                                    <form method="POST" action="{{ route('ventes.destroy', $vente) }}" class="inline"
                                                        onsubmit="return confirm('Supprimer cette vente ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Supprimer</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Total général</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalGeneral, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            @if ($ventes->isNotEmpty())
            <!-- Caisse -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mt-6" x-data="caisseVentes()" x-cloak>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Caisse</h3>
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jour de paiement</label>
                        <input type="date" x-model="caisseJour"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Moyen de paiement</label>
                        <select x-model="caissePaiement"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Tous</option>
                            <option value="cb">CB</option>
                            <option value="especes">Espèces</option>
                            <option value="cheque">Chèque</option>
                            <option value="internet">Internet</option>
                            <option value="virement">Virement</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4" x-show="caisseJour">
                    <div class="p-4 rounded-lg" :class="caisseTotal > 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'">
                        <p class="text-sm text-gray-600" x-show="caissePaiement === ''">
                            Total du <span class="font-medium" x-text="formatDate(caisseJour)"></span> :
                            <span class="text-xl font-bold text-gray-900 ml-1" x-text="formatPrix(caisseTotal)"></span>
                        </p>
                        <p class="text-sm text-gray-600" x-show="caissePaiement !== ''">
                            Total du <span class="font-medium" x-text="formatDate(caisseJour)"></span>
                            par <span class="font-medium" x-text="caissePaiementLabel"></span> :
                            <span class="text-xl font-bold text-gray-900 ml-1" x-text="formatPrix(caisseTotal)"></span>
                        </p>
                        <p class="text-xs text-gray-500 mt-1" x-text="caisseCount + ' vente(s)'"></p>
                    </div>
                </div>
                <div class="mt-3" x-show="!caisseJour">
                    <p class="text-sm text-gray-400">Choisissez un jour pour voir le total.</p>
                </div>
            </div>
            @endif

    <script>
        function caisseVentes() {
            const data = @json($caisseData);

            return {
                caisseJour: '',
                caissePaiement: '',

                get caisseFiltered() {
                    return data.filter(v => {
                        if (!v.jour || v.jour !== this.caisseJour) return false;
                        if (this.caissePaiement === 'cb' && !v.cb) return false;
                        if (this.caissePaiement === 'especes' && !v.especes) return false;
                        if (this.caissePaiement === 'cheque' && !v.cheque) return false;
                        if (this.caissePaiement === 'internet' && !v.internet) return false;
                        if (this.caissePaiement === 'virement' && !v.virement) return false;
                        return true;
                    });
                },

                get caisseTotal() {
                    return this.caisseFiltered.reduce((sum, v) => sum + v.total, 0);
                },

                get caisseCount() {
                    return this.caisseFiltered.length;
                },

                get caissePaiementLabel() {
                    return { cb: 'CB', especes: 'Espèces', cheque: 'Chèque', internet: 'Internet', virement: 'Virement' }[this.caissePaiement] || '';
                },

                formatPrix(val) {
                    return (val || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' \u20AC';
                },

                formatDate(d) {
                    if (!d) return '';
                    const [y, m, day] = d.split('-');
                    return `${day}/${m}/${y}`;
                }
            };
        }

        function ventesFilter() {
            return {
                filterClient: '',
                filterProduit: '',
                filterFacture: '',
                filterNomFacturation: '',

                showRow(row) {
                    if (this.filterClient && !row.client.toLowerCase().includes(this.filterClient.toLowerCase())) return false;
                    if (this.filterProduit && !row.produits.toLowerCase().includes(this.filterProduit.toLowerCase())) return false;
                    if (this.filterFacture === 'oui' && !row.facture) return false;
                    if (this.filterFacture === 'non' && row.facture) return false;
                    if (this.filterNomFacturation && !row.nom_facturation.toLowerCase().includes(this.filterNomFacturation.toLowerCase())) return false;
                    return true;
                },

                resetFilters() {
                    this.filterClient = '';
                    this.filterProduit = '';
                    this.filterFacture = '';
                    this.filterNomFacturation = '';
                }
            };
        }
    </script>
        </div>
    </div>
</x-app-layout>
