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

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @include('concours.partials.tabs', ['active' => 'commande-retraits'])

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Retrait Commandes</h3>
                <form action="{{ route('concours.commande-retraits.import', $concours) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-3">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                        class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        Importer CSV
                    </button>
                </form>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg" x-data="commandeRetraitsFilter()" x-cloak>
                @if ($commandes->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune commande importée pour le moment.
                    </div>
                @else
                    <!-- Filtres -->
                    <div class="px-4 pt-4 pb-2 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nom</label>
                            <input type="text" x-model="filterNom" placeholder="Nom..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Produit</label>
                            <input type="text" x-model="filterProduit" placeholder="Produit..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">N* Commande</label>
                            <input type="text" x-model="filterNumero" placeholder="Numero..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                            <select x-model="filterStatut"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                <option value="non_retire">Non retiré</option>
                                <option value="retire">Retiré</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N* Commande</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Emplacement / Boxes</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($commandes as $commande)
                                    <tr x-show="filterRow({{ json_encode([
                                        'nom' => $commande->nom,
                                        'prenom' => $commande->prenom,
                                        'produit' => $commande->produit,
                                        'numero' => $commande->numero_commande,
                                        'retire' => $commande->retire,
                                    ]) }})"
                                        class="{{ $commande->retire ? 'bg-green-50' : '' }}">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $commande->numero_commande }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $commande->date_commande->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ trim($commande->prenom . ' ' . $commande->nom) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $commande->produit }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $commande->quantite }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $commande->emplacement_boxes ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <form action="{{ route('concours.commande-retraits.toggle-retire', [$concours, $commande]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold transition
                                                        {{ $commande->retire
                                                            ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                                            : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                                    {{ $commande->retire ? 'Retiré' : 'A retirer' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function commandeRetraitsFilter() {
            return {
                filterNom: '',
                filterProduit: '',
                filterNumero: '',
                filterStatut: '',
                filterRow(row) {
                    if (this.filterNom && !(row.nom.toLowerCase().includes(this.filterNom.toLowerCase()) || row.prenom.toLowerCase().includes(this.filterNom.toLowerCase()))) return false;
                    if (this.filterProduit && !row.produit.toLowerCase().includes(this.filterProduit.toLowerCase())) return false;
                    if (this.filterNumero && !row.numero.toLowerCase().includes(this.filterNumero.toLowerCase())) return false;
                    if (this.filterStatut === 'retire' && !row.retire) return false;
                    if (this.filterStatut === 'non_retire' && row.retire) return false;
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
