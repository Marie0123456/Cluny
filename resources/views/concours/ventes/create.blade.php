<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">Nouvelle vente</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                x-data="venteForm()" x-cloak>
                <form method="POST" action="{{ route('concours.ventes.store', $concours) }}" @submit="prepareSubmit($event)">
                    @csrf

                    <!-- Nom client -->
                    <div class="mb-6">
                        <x-input-label for="nom_client" value="Nom du client" />
                        <x-text-input id="nom_client" name="nom_client" type="text" class="mt-1 block w-full" x-model="nomClient" required />
                    </div>

                    <!-- Lignes de produits -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Produits</h3>
                        <div class="space-y-3">
                            <template x-for="(ligne, index) in lignes" :key="index">
                                <div class="flex gap-3 items-center">
                                    <select x-model="ligne.produit_id" @change="updateLigneTotal(index)"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        <option value="">Choisir un produit</option>
                                        @foreach($produits as $produit)
                                            <option value="{{ $produit->id }}" data-prix="{{ $produit->prix_ttc }}">
                                                {{ $produit->nom }} — {{ number_format($produit->prix_ttc, 2, ',', ' ') }} &euro;
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="number" x-model.number="ligne.quantite" min="1"
                                        @input="updateLigneTotal(index)"
                                        class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-center"
                                        placeholder="Qté">
                                    <span class="w-24 text-sm text-gray-700 text-right font-medium" x-text="formatPrix(ligne.total)"></span>
                                    <button @click="removeLigne(index)" type="button"
                                        x-show="lignes.length > 1"
                                        class="text-red-500 hover:text-red-700 text-lg font-bold px-2">&times;</button>
                                </div>
                            </template>
                        </div>

                        <button @click="addLigne()" type="button"
                            class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            + Ajouter un produit
                        </button>

                        <!-- Total TTC -->
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end">
                            <span class="text-lg font-bold text-gray-900">
                                Total TTC : <span x-text="formatPrix(totalGeneral)"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Jour de paiement -->
                    <div class="mb-6">
                        <x-input-label for="jour_paiement" value="Jour de paiement (optionnel)" />
                        <x-text-input id="jour_paiement" name="jour_paiement" type="date" class="mt-1 block w-full" />
                    </div>

                    <!-- Moyens de paiement -->
                    <div class="mb-6">
                        <span class="block text-sm font-medium text-gray-700 mb-2">Moyen de paiement (optionnel)</span>
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="paiement_cb" value="1" x-model="paiementCb"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">CB</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="paiement_especes" value="1" x-model="paiementEspeces"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Espèces</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="paiement_cheque" value="1" x-model="paiementCheque"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Chèque</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="paiement_internet" value="1" x-model="paiementInternet"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Internet</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="paiement_virement" value="1" x-model="paiementVirement"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Virement</span>
                            </label>
                        </div>
                    </div>

                    <!-- N° de chèque -->
                    <div class="mb-6" x-show="paiementCheque" x-transition>
                        <x-input-label for="numero_cheque" value="Numéro de chèque" />
                        <x-text-input id="numero_cheque" name="numero_cheque" type="text" class="mt-1 block w-full" />
                    </div>

                    <!-- Facture -->
                    <div class="mb-6">
                        <span class="block text-sm font-medium text-gray-700 mb-2">Facture</span>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="facture" value="1" x-model="facture"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Oui</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="facture" value="0" x-model="facture"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Non</span>
                            </label>
                        </div>
                    </div>

                    <!-- Champs facturation -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg space-y-4" x-show="facture == '1'" x-transition>
                        <h4 class="text-sm font-medium text-gray-700">Informations de facturation</h4>

                        <div class="relative">
                            <x-input-label for="nom_facturation" value="Nom de facturation" />
                            <x-text-input id="nom_facturation" name="nom_facturation" type="text"
                                class="mt-1 block w-full" x-model="nomFacturation"
                                @input.debounce.300ms="searchClients()"
                                @focus="showClientsResults = true" />

                            <ul x-show="showClientsResults && clientsResultats.length > 0"
                                @click.away="showClientsResults = false"
                                class="absolute z-10 w-full bg-white border border-gray-200 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto">
                                <template x-for="client in clientsResultats" :key="client.id">
                                    <li @click="selectClient(client)"
                                        class="cursor-pointer hover:bg-indigo-50 px-4 py-2 text-sm">
                                        <span x-text="client.nom" class="font-medium"></span>
                                        <span x-show="client.email" x-text="' — ' + client.email" class="text-gray-500"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="telephone" value="Téléphone" />
                                <x-text-input id="telephone" name="telephone" type="tel" class="mt-1 block w-full" x-model="telephone" />
                            </div>
                            <div>
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" x-model="emailFacturation" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="adresse" value="Adresse" />
                            <textarea id="adresse" name="adresse" rows="2" x-model="adresseFacturation"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                        </div>
                    </div>

                    <!-- À retirer -->
                    <div class="mb-6">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="a_retirer" value="1" x-model="aRetirer"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">À retirer</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Si coché, une ligne sera ajoutée sur la page Retrait Commandes.</p>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-6">
                        <x-input-label for="commentaire" value="Commentaire (optionnel)" />
                        <textarea id="commentaire" name="commentaire" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    </div>

                    <!-- Hidden inputs for lignes (populated on submit) -->
                    <div id="lignes-hidden"></div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer la vente</x-primary-button>
                        <a href="{{ route('concours.ventes.index', $concours) }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function venteForm() {
            const produitsPrix = {
                @foreach($produits as $produit)
                    '{{ $produit->id }}': {{ $produit->prix_ttc }},
                @endforeach
            };

            return {
                nomClient: '',
                lignes: [{ produit_id: '', quantite: 1, total: 0 }],
                paiementCb: false,
                paiementEspeces: false,
                paiementCheque: false,
                paiementInternet: false,
                paiementVirement: false,
                facture: '0',
                nomFacturation: '',
                telephone: '',
                emailFacturation: '',
                adresseFacturation: '',
                clientsResultats: [],
                showClientsResults: false,
                aRetirer: false,

                get totalGeneral() {
                    return this.lignes.reduce((sum, l) => sum + (l.total || 0), 0);
                },

                addLigne() {
                    this.lignes.push({ produit_id: '', quantite: 1, total: 0 });
                },

                removeLigne(index) {
                    if (this.lignes.length > 1) {
                        this.lignes.splice(index, 1);
                    }
                },

                updateLigneTotal(index) {
                    const ligne = this.lignes[index];
                    const prix = produitsPrix[ligne.produit_id] || 0;
                    ligne.total = prix * (ligne.quantite || 0);
                },

                formatPrix(val) {
                    return (val || 0).toFixed(2).replace('.', ',') + ' \u20AC';
                },

                async searchClients() {
                    if (this.nomFacturation.length < 2) {
                        this.clientsResultats = [];
                        return;
                    }
                    const res = await fetch(`/api/clients-facturation/search?q=${encodeURIComponent(this.nomFacturation)}`);
                    this.clientsResultats = await res.json();
                    this.showClientsResults = true;
                },

                selectClient(client) {
                    this.nomFacturation = client.nom;
                    this.telephone = client.telephone || '';
                    this.emailFacturation = client.email || '';
                    this.adresseFacturation = client.adresse || '';
                    this.showClientsResults = false;
                    this.clientsResultats = [];
                },

                prepareSubmit(event) {
                    // Validate at least one product selected
                    const validLignes = this.lignes.filter(l => l.produit_id && l.quantite > 0);
                    if (validLignes.length === 0) {
                        event.preventDefault();
                        alert('Veuillez ajouter au moins un produit.');
                        return;
                    }

                    // Create hidden inputs for lignes
                    const container = document.getElementById('lignes-hidden');
                    container.innerHTML = '';
                    validLignes.forEach((ligne, i) => {
                        const pid = document.createElement('input');
                        pid.type = 'hidden';
                        pid.name = `lignes[${i}][produit_id]`;
                        pid.value = ligne.produit_id;
                        container.appendChild(pid);

                        const qty = document.createElement('input');
                        qty.type = 'hidden';
                        qty.name = `lignes[${i}][quantite]`;
                        qty.value = ligne.quantite;
                        container.appendChild(qty);
                    });
                }
            };
        }
    </script>
</x-app-layout>
