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
                            <label class="block text-xs font-medium text-gray-500 mb-1">Epreuve</label>
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
                        <button @click="resetFilters()" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Reinitialiser les filtres</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N. Epreuve</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de modif</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jour paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
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
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
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
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900">Totaux</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPf, 2, ',', ' ') }} &euro;</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPrix, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

    <script>
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
