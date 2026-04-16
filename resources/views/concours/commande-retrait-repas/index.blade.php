@push('head')
    <meta http-equiv="refresh" content="300">
@endpush

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

            @include('concours.partials.tabs', ['active' => 'commande-retrait-repas'])

            <div class="sm:flex sm:justify-between sm:items-center mb-4 space-y-3 sm:space-y-0">
                <h3 class="text-lg font-medium text-gray-900">Retrait Repas</h3>
                <form action="{{ route('concours.commande-retrait-repas.import', $concours) }}" method="POST" enctype="multipart/form-data" class="sm:flex sm:items-center sm:gap-3 space-y-2 sm:space-y-0">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                        class="block w-full sm:w-auto text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
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

            <div class="bg-white shadow-sm sm:rounded-lg" x-data="commandeRetraitRepasApp({{ Js::from($commandes->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_commande,
                'date' => $c->date_commande->format('d/m/Y'),
                'nom' => $c->nom,
                'prenom' => $c->prenom,
                'produit' => $c->produit,
                'quantite' => $c->quantite,
                'quantite_retiree' => $c->quantite_retiree,
                'emplacement_boxes' => $c->emplacement_boxes,
                'note_client' => $c->note_client,
                'retired_by_name' => $c->retiredByUser?->name,
                'retired_at' => $c->retired_at?->format('d/m/Y à H:i'),
            ])) }}, {{ Js::from($concours->id) }})" x-cloak>
                @if ($commandes->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune commande repas importée pour le moment.
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
                            <label class="block text-xs font-medium text-gray-500 mb-1">N° Commande</label>
                            <input type="text" x-model="filterNumero" placeholder="Numero..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                            <select x-model="filterStatut"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                <option value="aucun">À retirer</option>
                                <option value="partiel">Partiel</option>
                                <option value="complet">Retiré</option>
                            </select>
                        </div>
                    </div>

                    {{-- Mobile card layout --}}
                    <div class="sm:hidden divide-y divide-gray-200">
                        <template x-for="c in filtered" :key="c.id">
                            <div :class="rowBg(c)" class="p-4">
                                <div class="flex items-start justify-between mb-1 gap-2">
                                    <span class="font-medium text-sm text-gray-900" x-text="(c.prenom + ' ' + c.nom).trim()"></span>
                                    <span :class="badgeClass(c)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold whitespace-nowrap"
                                          :title="c.retired_by_name ? 'Dernier retrait par ' + c.retired_by_name + ' le ' + c.retired_at : ''">
                                        <span x-text="statutLabel(c)"></span>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-700 mb-1" x-text="c.produit + ' x' + c.quantite"></div>
                                <template x-if="c.note_client">
                                    <div class="text-xs text-amber-700 bg-amber-50 rounded px-2 py-1 mb-1" x-text="c.note_client"></div>
                                </template>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                                    <span x-text="'N° ' + c.numero"></span>
                                    <span x-text="c.date"></span>
                                </div>

                                <div class="flex items-center gap-1 mb-2">
                                    <button @click="setQty(c, c.quantite_retiree - 1)" :disabled="c.quantite_retiree <= 0"
                                        class="px-2 py-1 bg-gray-200 rounded text-sm disabled:opacity-40">−</button>
                                    <span class="font-mono text-sm px-2" x-text="c.quantite_retiree + ' / ' + c.quantite"></span>
                                    <button @click="setQty(c, c.quantite_retiree + 1)" :disabled="c.quantite_retiree >= c.quantite"
                                        class="px-2 py-1 bg-gray-200 rounded text-sm disabled:opacity-40">+</button>
                                    <button @click="setQty(c, c.quantite)" :disabled="c.quantite_retiree >= c.quantite"
                                        class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded text-xs disabled:opacity-40">Tout</button>
                                    <button @click="setQty(c, 0)" :disabled="c.quantite_retiree === 0"
                                        class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs disabled:opacity-40">Reset</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="openEdit(c)" class="text-xs text-indigo-600 hover:text-indigo-800">Éditer</button>
                                    <button @click="confirmDelete(c)" class="text-xs text-red-600 hover:text-red-800">Supprimer</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Desktop table layout --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Commande</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Retrait</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="c in filtered" :key="c.id">
                                    <tr :class="rowBg(c)">
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="c.numero"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="c.date"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="(c.prenom + ' ' + c.nom).trim()"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <span x-text="c.produit"></span>
                                            <span class="text-gray-400 text-xs" x-text="'x' + c.quantite"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="c.note_client ?? ''"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="setQty(c, c.quantite_retiree - 1)" :disabled="c.quantite_retiree <= 0"
                                                    class="px-2 py-1 bg-gray-200 rounded text-sm disabled:opacity-40">−</button>
                                                <span :class="badgeClass(c)" class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold font-mono whitespace-nowrap"
                                                      :title="c.retired_by_name ? 'Dernier retrait par ' + c.retired_by_name + ' le ' + c.retired_at : ''"
                                                      x-text="c.quantite_retiree + ' / ' + c.quantite"></span>
                                                <button @click="setQty(c, c.quantite_retiree + 1)" :disabled="c.quantite_retiree >= c.quantite"
                                                    class="px-2 py-1 bg-gray-200 rounded text-sm disabled:opacity-40">+</button>
                                                <button @click="setQty(c, c.quantite)" :disabled="c.quantite_retiree >= c.quantite"
                                                    class="ml-1 px-2 py-1 bg-green-100 text-green-800 rounded text-xs disabled:opacity-40">Tout</button>
                                                <button @click="setQty(c, 0)" :disabled="c.quantite_retiree === 0"
                                                    class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs disabled:opacity-40">Reset</button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                            <button @click="openEdit(c)" class="text-indigo-600 hover:text-indigo-800 text-xs mr-3">Éditer</button>
                                            <button @click="confirmDelete(c)" class="text-red-600 hover:text-red-800 text-xs">Supprimer</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Edit modal --}}
                <div x-show="editing" @keydown.escape.window="editing = null"
                     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                     x-transition.opacity>
                    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6" @click.stop>
                        <h3 class="text-lg font-semibold mb-4">Modifier la commande repas</h3>
                        <template x-if="editing">
                            <form @submit.prevent="saveEdit()">
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Prénom</label>
                                        <input type="text" x-model="editForm.prenom" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Nom</label>
                                        <input type="text" x-model="editForm.nom" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Produit</label>
                                    <input type="text" x-model="editForm.produit" required class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Quantité</label>
                                        <input type="number" min="1" x-model.number="editForm.quantite" required class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Emplacement/box</label>
                                        <input type="text" x-model="editForm.emplacement_boxes" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Note client</label>
                                    <textarea x-model="editForm.note_client" rows="2" class="w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="editing = null"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Annuler</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Enregistrer</button>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function commandeRetraitRepasApp(initialCommandes, concoursId) {
            return {
                concoursId,
                commandes: initialCommandes,
                filterNom: '',
                filterProduit: '',
                filterNumero: '',
                filterStatut: '',
                editing: null,
                editForm: {},

                get filtered() {
                    return this.commandes.filter(c => {
                        if (this.filterNom) {
                            const q = this.filterNom.toLowerCase();
                            if (!(c.nom?.toLowerCase().includes(q) || c.prenom?.toLowerCase().includes(q))) return false;
                        }
                        if (this.filterProduit && !c.produit.toLowerCase().includes(this.filterProduit.toLowerCase())) return false;
                        if (this.filterNumero && !c.numero.toLowerCase().includes(this.filterNumero.toLowerCase())) return false;
                        if (this.filterStatut && this.statut(c) !== this.filterStatut) return false;
                        return true;
                    });
                },

                statut(c) {
                    if (c.quantite_retiree <= 0) return 'aucun';
                    if (c.quantite_retiree >= c.quantite) return 'complet';
                    return 'partiel';
                },

                statutLabel(c) {
                    const s = this.statut(c);
                    if (s === 'complet') return 'Retiré';
                    if (s === 'partiel') return 'Partiel ' + c.quantite_retiree + '/' + c.quantite;
                    return 'À retirer';
                },

                rowBg(c) {
                    const s = this.statut(c);
                    if (s === 'complet') return 'bg-green-50';
                    if (s === 'partiel') return 'bg-amber-50';
                    return '';
                },

                badgeClass(c) {
                    const s = this.statut(c);
                    if (s === 'complet') return 'bg-green-100 text-green-800';
                    if (s === 'partiel') return 'bg-amber-100 text-amber-800';
                    return 'bg-gray-100 text-gray-800';
                },

                async setQty(c, qty) {
                    qty = Math.max(0, Math.min(c.quantite, qty));
                    if (qty === c.quantite_retiree) return;
                    const prev = c.quantite_retiree;
                    c.quantite_retiree = qty;
                    try {
                        const res = await fetch(`/concours/${this.concoursId}/commande-retrait-repas/${c.id}/set-quantite`, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ quantite_retiree: qty }),
                        });
                        if (!res.ok) throw new Error(res.status);
                        const data = await res.json();
                        c.retired_by_name = data.retired_by_name;
                        c.retired_at = data.retired_at;
                    } catch (e) {
                        c.quantite_retiree = prev;
                        alert('Erreur lors de la mise à jour.');
                    }
                },

                openEdit(c) {
                    this.editing = c;
                    this.editForm = {
                        prenom: c.prenom ?? '',
                        nom: c.nom ?? '',
                        produit: c.produit,
                        quantite: c.quantite,
                        emplacement_boxes: c.emplacement_boxes ?? '',
                        note_client: c.note_client ?? '',
                    };
                },

                async saveEdit() {
                    const c = this.editing;
                    try {
                        const res = await fetch(`/concours/${this.concoursId}/commande-retrait-repas/${c.id}`, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.editForm),
                        });
                        if (!res.ok) throw new Error(res.status);
                        Object.assign(c, this.editForm);
                        if (c.quantite_retiree > c.quantite) c.quantite_retiree = c.quantite;
                        this.editing = null;
                    } catch (e) {
                        alert('Erreur lors de la sauvegarde.');
                    }
                },

                async confirmDelete(c) {
                    if (!confirm(`Supprimer la commande ${c.numero} (${c.produit}) ?\n\nElle ne sera pas réimportée depuis le CSV.`)) return;
                    try {
                        const res = await fetch(`/concours/${this.concoursId}/commande-retrait-repas/${c.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                        if (!res.ok) throw new Error(res.status);
                        this.commandes = this.commandes.filter(x => x.id !== c.id);
                    } catch (e) {
                        alert('Erreur lors de la suppression.');
                    }
                },
            };
        }
    </script>
</x-app-layout>
