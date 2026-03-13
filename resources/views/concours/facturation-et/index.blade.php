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

            @include('concours.partials.tabs', ['active' => 'facturation-et'])

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Facturation ET</h3>
                @if ($modifications->isNotEmpty())
                    <a href="{{ route('concours.facturation-et.export-csv', $concours) }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                        Export CSV
                    </a>
                @endif
            </div>

            <!-- Totaux -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Modifications payantes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $modifications->count() }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Prix</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalPrix, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total PF</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalPf, 2, ',', ' ') }} &euro;</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg" x-data="facturationFilter()" x-cloak>
                @if ($modifications->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune modification payante pour le moment.
                    </div>
                @else
                    <!-- Filtres -->
                    <div class="px-4 pt-4 pb-2 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Épreuve</label>
                            <select x-model="filterEpreuve"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Toutes</option>
                                @php
                                    $epreuveNums = $modifications->map(fn($m) => $m->engagement->epreuve)->filter()->unique('id')->sortBy('numero');
                                @endphp
                                @foreach ($epreuveNums as $ep)
                                    <option value="{{ $ep->numero }}">{{ $ep->numero }} - {{ $ep->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Cavalier</label>
                            <input type="text" x-model="filterCavalier" placeholder="Nom du cavalier..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
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
                    <div x-show="filterEpreuve || filterCavalier || filterFacture || filterNomFacturation" class="px-4 pb-2">
                        <button @click="resetFilters()" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Réinitialiser les filtres</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Épreuve</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de modif</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PU HT</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix TTC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jour paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($modifications as $mod)
                                    <tr x-show="showRow({{ json_encode([
                                        'epreuve' => (string) ($mod->engagement->epreuve->numero ?? ''),
                                        'cavalier' => trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                                        'facture' => $mod->facture,
                                        'nom_facturation' => $mod->clientFacturation->nom ?? '',
                                    ]) }})">
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            {{ $mod->engagement->epreuve->numero ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->engagement->cheval->nom ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->pf ? number_format($mod->pf, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @if ($mod->prix)
                                                @php $puHt = round(((float) $mod->prix - (float) $mod->pf) / 1.055, 2); @endphp
                                                {{ number_format($puHt, 2, ',', ' ') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            {{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @php
                                                $paiements = [];
                                                if ($mod->paiement_cb) $paiements[] = 'CB';
                                                if ($mod->paiement_especes) $paiements[] = 'Espèces';
                                                if ($mod->paiement_cheque) $paiements[] = 'Chèque';
                                                if ($mod->paiement_internet) $paiements[] = 'Internet';
                                                if ($mod->paiement_virement) $paiements[] = 'Virement';
                                            @endphp
                                            {{ $paiements ? implode(', ', $paiements) : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($mod->facture && $mod->clientFacturation)
                                                <a href="{{ route('concours.factures.show', [$concours, $mod->clientFacturation]) }}"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition">
                                                    {{ $mod->clientFacturation->nom }}
                                                </a>
                                            @elseif ($mod->facture)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Oui</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    Non
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <button type="button" onclick="toggleEditRowET({{ $mod->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Éditer</button>
                                        </td>
                                    </tr>
                                    {{-- Inline edit row --}}
                                    <tr id="edit-row-et-{{ $mod->id }}" class="hidden bg-gray-50">
                                        <td colspan="11" class="px-4 py-4">
                                            <form method="POST" action="{{ route('modifications.update-paiement', $mod) }}" class="space-y-4">
                                                @csrf
                                                @method('PATCH')

                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                                    {{-- Jour de paiement --}}
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">Jour de paiement</label>
                                                        <input type="date" name="jour_paiement" value="{{ $mod->jour_paiement?->format('Y-m-d') }}"
                                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                    </div>
                                                    {{-- Moyen de paiement --}}
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">Moyen de paiement</label>
                                                        <div class="flex gap-3 mt-1">
                                                            <label class="inline-flex items-center text-sm">
                                                                <input type="checkbox" name="paiement_cb" value="1" {{ $mod->paiement_cb ? 'checked' : '' }}
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ml-1">CB</span>
                                                            </label>
                                                            <label class="inline-flex items-center text-sm">
                                                                <input type="checkbox" name="paiement_especes" value="1" {{ $mod->paiement_especes ? 'checked' : '' }}
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ml-1">Espèces</span>
                                                            </label>
                                                            <label class="inline-flex items-center text-sm">
                                                                <input type="checkbox" name="paiement_cheque" value="1" {{ $mod->paiement_cheque ? 'checked' : '' }}
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ml-1">Chèque</span>
                                                            </label>
                                                            <label class="inline-flex items-center text-sm">
                                                                <input type="checkbox" name="paiement_internet" value="1" {{ $mod->paiement_internet ? 'checked' : '' }}
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ml-1">Internet</span>
                                                            </label>
                                                            <label class="inline-flex items-center text-sm">
                                                                <input type="checkbox" name="paiement_virement" value="1" {{ $mod->paiement_virement ? 'checked' : '' }}
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ml-1">Virement</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Facture section --}}
                                                <div x-data="{ facture: {{ $mod->facture ? 'true' : 'false' }}, nomFacturation: '{{ addslashes($mod->clientFacturation->nom ?? '') }}', telephone: '{{ addslashes($mod->clientFacturation->telephone ?? '') }}', emailFacturation: '{{ addslashes($mod->clientFacturation->email ?? '') }}', adresseFacturation: '{{ addslashes($mod->clientFacturation->adresse ?? '') }}', clientsResultats: [], showClientsResults: false }">
                                                    <div class="flex items-center gap-4 mb-3">
                                                        <span class="text-xs font-medium text-gray-500">Facture</span>
                                                        <label class="inline-flex items-center text-sm">
                                                            <input type="radio" name="facture" value="1" :checked="facture" @change="facture = true"
                                                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span class="ml-1">Oui</span>
                                                        </label>
                                                        <label class="inline-flex items-center text-sm">
                                                            <input type="radio" name="facture" value="0" :checked="!facture" @change="facture = false"
                                                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span class="ml-1">Non</span>
                                                        </label>
                                                    </div>
                                                    <div x-show="facture" class="grid grid-cols-2 md:grid-cols-4 gap-4 p-3 bg-white rounded-lg border border-gray-200">
                                                        <div class="relative">
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Nom de facturation</label>
                                                            <input type="text" name="nom_facturation" x-model="nomFacturation"
                                                                @input.debounce.300ms="if (nomFacturation.length >= 2) { fetch('/api/clients-facturation/search?q=' + encodeURIComponent(nomFacturation)).then(r => r.json()).then(d => { clientsResultats = d; showClientsResults = true; }); } else { clientsResultats = []; }"
                                                                @focus="showClientsResults = true"
                                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                            <ul x-show="showClientsResults && clientsResultats.length > 0"
                                                                @click.away="showClientsResults = false"
                                                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto divide-y divide-gray-100">
                                                                <template x-for="client in clientsResultats" :key="client.id">
                                                                    <li @click="nomFacturation = client.nom; telephone = client.telephone || ''; emailFacturation = client.email || ''; adresseFacturation = client.adresse || ''; showClientsResults = false; clientsResultats = [];"
                                                                        class="cursor-pointer hover:bg-indigo-50 px-4 py-2 text-sm" x-text="client.nom"></li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Téléphone</label>
                                                            <input type="text" name="telephone" x-model="telephone"
                                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                                                            <input type="email" name="email" x-model="emailFacturation"
                                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Adresse</label>
                                                            <input type="text" name="adresse" x-model="adresseFacturation"
                                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex justify-end">
                                                    <button type="button" onclick="toggleEditRowET({{ $mod->id }})" class="mr-3 text-sm text-gray-600 hover:text-gray-800">Fermer</button>
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                                        Enregistrer
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900">Totaux</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPf, 2, ',', ' ') }} &euro;</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format(round(($totalPrix - $totalPf) / 1.055, 2), 2, ',', ' ') }} &euro;</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPrix, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            @if ($modifications->isNotEmpty())
            <!-- Caisse -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mt-6" x-data="caisseET()" x-cloak>
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
                        <p class="text-xs text-gray-500 mt-1" x-text="caisseCount + ' modification(s)'"></p>
                    </div>
                </div>
                <div class="mt-3" x-show="!caisseJour">
                    <p class="text-sm text-gray-400">Choisissez un jour pour voir le total.</p>
                </div>
            </div>
            @endif

    <script>
        function caisseET() {
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

        function toggleEditRowET(modId) {
            const row = document.getElementById('edit-row-et-' + modId);
            if (row) {
                row.classList.toggle('hidden');
            }
        }

        function facturationFilter() {
            return {
                filterEpreuve: '',
                filterCavalier: '',
                filterFacture: '',
                filterNomFacturation: '',

                showRow(row) {
                    if (this.filterEpreuve && row.epreuve !== this.filterEpreuve) return false;
                    if (this.filterCavalier && !row.cavalier.toLowerCase().includes(this.filterCavalier.toLowerCase())) return false;
                    if (this.filterFacture === 'oui' && !row.facture) return false;
                    if (this.filterFacture === 'non' && row.facture) return false;
                    if (this.filterNomFacturation && !row.nom_facturation.toLowerCase().includes(this.filterNomFacturation.toLowerCase())) return false;
                    return true;
                },

                resetFilters() {
                    this.filterEpreuve = '';
                    this.filterCavalier = '';
                    this.filterFacture = '';
                    this.filterNomFacturation = '';
                }
            };
        }
    </script>
        </div>
    </div>
</x-app-layout>
