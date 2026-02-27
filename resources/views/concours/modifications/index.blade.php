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
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('concours.partials.tabs', ['active' => 'modifications'])

            <!-- Changement de cheval form -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                x-data="changementCheval()" x-cloak>

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Changement de cheval</h3>
                    <button @click="open = !open"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        <span x-text="open ? 'Fermer' : 'Nouveau changement'"></span>
                    </button>
                </div>

                <div x-show="open" x-transition class="space-y-4">
                    <!-- Step 1: Epreuve -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Epreuve</label>
                        <select x-model="epreuveId" @change="onEpreuveChange()"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choisir l'epreuve</option>
                            @foreach($epreuves as $epreuve)
                                <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Step 2: Cavalier / Engagement -->
                    <div x-show="cavaliers.length > 0" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cavalier (et cheval actuel)</label>
                        <input type="text" x-model="searchCavalier"
                            @input="filterCavaliers()"
                            @focus="showCavalierList = true"
                            placeholder="Tapez un nom de cavalier ou de cheval..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                        <div x-show="selectedCavalierLabel" class="mt-1 text-sm text-indigo-700 font-medium">
                            <span x-text="selectedCavalierLabel"></span>
                        </div>

                        <ul x-show="showCavalierList && filteredCavaliers.length > 0"
                            @click.away="showCavalierList = false"
                            class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                            <template x-for="c in filteredCavaliers" :key="c.engagement_id">
                                <li @click="selectCavalier(c)"
                                    class="cursor-pointer hover:bg-indigo-50 px-4 py-3 border-b border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-semibold text-sm text-gray-900" x-text="`${c.cavalier_nom} ${c.cavalier_prenom}`"></span>
                                            <span x-show="c.numero_depart" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600" x-text="`N°${c.numero_depart}`"></span>
                                        </div>
                                        <span class="text-sm text-gray-500" x-text="c.cheval_nom"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Step 3: Nouveau cheval -->
                    <div x-show="engagementId">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau cheval</label>

                        <label class="inline-flex items-center mb-3 cursor-pointer">
                            <input type="checkbox" x-model="isNouveauCheval" @change="resetCheval()"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Nouveau cheval (pas encore dans la base)</span>
                        </label>

                        <div x-show="!isNouveauCheval" class="relative">
                            <input type="text" x-model="searchCheval"
                                @input.debounce.150ms="searchChevaux()"
                                @focus="showResults = true"
                                placeholder="Rechercher un cheval par nom..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            <div x-show="selectedChevalNom" class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <span x-text="selectedChevalNom"></span>
                                    <span x-show="selectedChevalSire" x-text="` (SIRE: ${selectedChevalSire})`" class="text-green-600"></span>
                                </span>
                                <button type="button" @click="resetCheval()" class="text-gray-400 hover:text-red-500 text-sm">&times;</button>
                            </div>

                            <ul x-show="showResults && resultatsChevaux.length > 0"
                                @click.away="showResults = false"
                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 max-h-64 overflow-y-auto divide-y divide-gray-100">
                                <template x-for="ch in resultatsChevaux" :key="ch.id">
                                    <li @click="selectCheval(ch)"
                                        class="cursor-pointer hover:bg-indigo-50 px-4 py-3 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-900" x-text="ch.nom"></span>
                                            <span x-show="ch.num_sire" class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded" x-text="`SIRE: ${ch.num_sire}`"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="isNouveauCheval" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du cheval</label>
                                <input type="text" x-model="nouveauChevalNom"
                                    placeholder="Nom du cheval"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numero de SIRE</label>
                                <input type="text" x-model="nouveauChevalSire"
                                    placeholder="Ex: 12345678A"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div x-show="nouveauChevalId || (isNouveauCheval && nouveauChevalNom)">
                        <form method="POST" action="{{ route('concours.modifications.changement-cheval', $concours) }}">
                            @csrf
                            <input type="hidden" name="engagement_id" :value="engagementId">
                            <template x-if="!isNouveauCheval">
                                <input type="hidden" name="nouveau_cheval_id" :value="nouveauChevalId">
                            </template>
                            <template x-if="isNouveauCheval">
                                <div>
                                    <input type="hidden" name="nouveau_cheval_nom" :value="nouveauChevalNom">
                                    <input type="hidden" name="nouveau_cheval_num_sire" :value="nouveauChevalSire">
                                </div>
                            </template>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Valider le changement
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invitation form -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                x-data="invitationForm()" x-cloak>

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Invitation (ajout engagement)</h3>
                    <button @click="open = !open"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        <span x-text="open ? 'Fermer' : 'Nouvelle invitation'"></span>
                    </button>
                </div>

                <div x-show="open" x-transition>
                    <form method="POST" action="{{ route('concours.modifications.invitation', $concours) }}" class="space-y-4">
                        @csrf

                        <!-- Step 1: Epreuve -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Epreuve</label>
                            <select x-model="epreuveId" @change="onEpreuveChange()" name="epreuve_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir l'epreuve</option>
                                @foreach($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                                @endforeach
                            </select>
                            <div x-show="epreuveId" class="mt-2 text-sm">
                                <span class="text-gray-600">Prix epreuve :</span>
                                <span class="font-semibold text-gray-900" x-text="epreuvePrix ? epreuvePrix.toFixed(2).replace('.', ',') + ' €' : '-'"></span>
                            </div>
                        </div>

                        <!-- Step 2: Cavalier -->
                        <div x-show="epreuveId">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cavalier</label>

                            <div class="flex gap-4 mb-3">
                                <label class="flex items-center gap-2">
                                    <input type="radio" x-model="cavalierMode" value="existing"
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Dans la liste des engages</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" x-model="cavalierMode" value="new"
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Nouveau cavalier</span>
                                </label>
                            </div>

                            <!-- Existing cavalier -->
                            <div x-show="cavalierMode === 'existing'" class="relative">
                                <input type="text" x-model="searchCavalier"
                                    @input="filterCavaliers()"
                                    @focus="showCavalierList = true"
                                    placeholder="Rechercher un cavalier..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                                <div x-show="selectedCavalierLabel" class="mt-1 text-sm text-indigo-700 font-medium">
                                    <span x-text="selectedCavalierLabel"></span>
                                </div>

                                <input type="hidden" name="cavalier_id" :value="cavalierId">

                                <ul x-show="showCavalierList && filteredCavaliers.length > 0"
                                    @click.away="showCavalierList = false"
                                    class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                    <template x-for="c in filteredCavaliers" :key="c.cavalier_id || c.engagement_id">
                                        <li @click="selectCavalier(c)"
                                            class="cursor-pointer hover:bg-indigo-50 px-4 py-3 border-b border-gray-100">
                                            <span class="font-semibold text-sm text-gray-900" x-text="`${c.cavalier_nom} ${c.cavalier_prenom}`"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <!-- New cavalier -->
                            <div x-show="cavalierMode === 'new'" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                                        <input type="text" name="nouveau_cavalier_nom" x-model="nouveauCavalierNom"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Prenom</label>
                                        <input type="text" name="nouveau_cavalier_prenom" x-model="nouveauCavalierPrenom"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Numero de licence</label>
                                    <input type="text" name="nouveau_cavalier_num_licence" x-model="nouveauCavalierLicence"
                                        placeholder="Ex: 1234567"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- GN checkbox: only visible for Pro épreuves on GN concours -->
                            @if ($concours->grand_national)
                                <div class="mt-3" x-show="epreuveTypeDetecte === 'pro'">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_gn" value="1" x-model="isGn"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700">GN (Grand National)</span>
                                    </label>
                                </div>
                            @endif
                        </div>

                        <!-- Step 3: Cheval -->
                        <div x-show="cavalierId || (cavalierMode === 'new' && nouveauCavalierNom)">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cheval</label>

                            <label class="inline-flex items-center mb-3 cursor-pointer">
                                <input type="checkbox" x-model="isNouveauCheval" @change="resetCheval()"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Nouveau cheval</span>
                            </label>

                            <div x-show="!isNouveauCheval" class="relative">
                                <input type="text" x-model="searchCheval"
                                    @input.debounce.150ms="searchChevaux()"
                                    @focus="showChevalResults = true"
                                    placeholder="Rechercher un cheval par nom..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="hidden" name="cheval_id" :value="chevalId">

                                <div x-show="selectedChevalNom" class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <span x-text="selectedChevalNom"></span>
                                    </span>
                                    <button type="button" @click="resetCheval()" class="text-gray-400 hover:text-red-500 text-sm">&times;</button>
                                </div>

                                <ul x-show="showChevalResults && chevalResults.length > 0"
                                    @click.away="showChevalResults = false"
                                    class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 max-h-64 overflow-y-auto divide-y divide-gray-100">
                                    <template x-for="ch in chevalResults" :key="ch.id">
                                        <li @click="selectCheval(ch)"
                                            class="cursor-pointer hover:bg-indigo-50 px-4 py-3 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold text-gray-900" x-text="ch.nom"></span>
                                                <span x-show="ch.num_sire" class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded" x-text="`SIRE: ${ch.num_sire}`"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div x-show="isNouveauCheval" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du cheval</label>
                                    <input type="text" name="nouveau_cheval_nom" x-model="nouveauChevalNom"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Numero de SIRE</label>
                                    <input type="text" name="nouveau_cheval_num_sire" x-model="nouveauChevalSire"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Type de compte + numero -->
                        <div x-show="chevalId || (isNouveauCheval && nouveauChevalNom)" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de compte</label>
                                <select name="type_compte" x-model="typeCompte"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choisir</option>
                                    <option value="Licence">Licence</option>
                                    <option value="Compte">Compte</option>
                                    <option value="Club">Club</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numero de compte</label>
                                <input type="text" name="numero_compte" x-model="numeroCompte"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Step 5: Prix calculated -->
                        <div x-show="chevalId || (isNouveauCheval && nouveauChevalNom)" class="p-4 bg-indigo-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Prix total :</p>
                                    <p class="text-xl font-bold text-indigo-900" x-text="calculatedPrix.toFixed(2).replace('.', ',') + ' €'"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Part Federale :</p>
                                    <p class="text-lg font-semibold text-indigo-700" x-text="calculatedPf.toFixed(2).replace('.', ',') + ' €'"></p>
                                </div>
                            </div>
                            <input type="hidden" name="prix" :value="calculatedPrix">
                            <input type="hidden" name="pf" :value="calculatedPf">
                        </div>

                        <!-- Step 6: Jour de paiement -->
                        <div x-show="chevalId || (isNouveauCheval && nouveauChevalNom)">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jour de paiement (optionnel)</label>
                            <input type="date" name="jour_paiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Step 7: Moyen de paiement -->
                        <div x-show="chevalId || (isNouveauCheval && nouveauChevalNom)">
                            <span class="block text-sm font-medium text-gray-700 mb-2">Moyen de paiement (optionnel)</span>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="paiement_cb" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">CB</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="paiement_especes" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Especes</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="paiement_cheque" value="1" x-model="paiementCheque"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Cheque</span>
                                </label>
                            </div>
                        </div>

                        <!-- Numero cheque -->
                        <div x-show="paiementCheque" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numero de cheque</label>
                            <input type="text" name="numero_cheque"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Step 8: Facture -->
                        <div x-show="chevalId || (isNouveauCheval && nouveauChevalNom)">
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
                        <div class="p-4 bg-gray-50 rounded-lg space-y-4" x-show="facture == '1'" x-transition>
                            <h4 class="text-sm font-medium text-gray-700">Informations de facturation</h4>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de facturation</label>
                                <input type="text" name="nom_facturation" x-model="nomFacturation"
                                    @input.debounce.300ms="searchClients()"
                                    @focus="showClientsResults = true"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

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

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Telephone</label>
                                    <input type="tel" name="telephone" x-model="telephone"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" x-model="emailFacturation"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                                <textarea name="adresse" rows="2" x-model="adresseFacturation"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div x-show="(chevalId || (isNouveauCheval && nouveauChevalNom)) && (cavalierId || (cavalierMode === 'new' && nouveauCavalierNom))">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Valider l'invitation
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Non-partant form -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                x-data="nonPartantForm()" x-cloak>

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Non-partant</h3>
                    <button @click="open = !open"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        <span x-text="open ? 'Fermer' : 'Declarer un non-partant'"></span>
                    </button>
                </div>

                <div x-show="open" x-transition class="space-y-4">
                    <form method="POST" action="{{ route('concours.modifications.non-partant', $concours) }}" class="space-y-4">
                        @csrf

                        <!-- Epreuve -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Epreuve</label>
                            <select x-model="epreuveId" @change="onEpreuveChange()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir l'epreuve</option>
                                @foreach($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Cavalier -->
                        <div x-show="cavaliers.length > 0" class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cavalier</label>
                            <input type="text" x-model="searchCavalier"
                                @input="filterCavaliers()"
                                @focus="showCavalierList = true"
                                placeholder="Rechercher un cavalier..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            <div x-show="selectedCavalierLabel" class="mt-1 text-sm text-indigo-700 font-medium">
                                <span x-text="selectedCavalierLabel"></span>
                            </div>

                            <input type="hidden" name="engagement_id" :value="engagementId">

                            <ul x-show="showCavalierList && filteredCavaliers.length > 0"
                                @click.away="showCavalierList = false"
                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                <template x-for="c in filteredCavaliers" :key="c.engagement_id">
                                    <li @click="selectCavalier(c)"
                                        class="cursor-pointer hover:bg-indigo-50 px-4 py-3 border-b border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-semibold text-sm text-gray-900" x-text="`${c.cavalier_nom} ${c.cavalier_prenom}`"></span>
                                                <span x-show="c.numero_depart" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600" x-text="`N°${c.numero_depart}`"></span>
                                            </div>
                                            <span class="text-sm text-gray-500" x-text="c.cheval_nom"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Submit -->
                        <div x-show="engagementId">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                Declarer non-partant
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modifications table -->
            <div class="bg-white shadow-sm sm:rounded-lg">
                @php
                    $visibleMods = $modifications->filter(fn($m) => $m->statut->value !== 'supprime');
                @endphp

                @if ($visibleMods->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune modification pour le moment.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Epreuve</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Depart</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Compte</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($visibleMods->sortBy(fn($m) => $m->statut->value === 'en_attente' ? 0 : 1) as $mod)
                                    <tr class="{{ $mod->statut->value === 'fait' ? 'opacity-50' : '' }}">
                                        {{-- Type --}}
                                        <td class="px-4 py-2 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        {{-- Epreuve --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $mod->engagement->epreuve->numero ?? '-' }}
                                        </td>
                                        {{-- Depart --}}
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            @if ($mod->type === \App\Enums\ModificationType::NON_PARTANT)
                                                <span class="font-bold text-red-600">NP</span>
                                            @else
                                                {{ $mod->engagement->numero_depart ?? '-' }}
                                            @endif
                                        </td>
                                        {{-- Nom (numero licence) --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <span class="font-medium">{{ $mod->engagement->cavalier->nom ?? '' }} {{ $mod->engagement->cavalier->prenom ?? '' }}</span>
                                            @if ($mod->engagement->cavalier->num_licence ?? null)
                                                <div class="text-xs text-gray-500">({{ $mod->engagement->cavalier->num_licence }})</div>
                                            @endif
                                        </td>
                                        {{-- Cheval (numero de sire) --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            @if ($mod->type === \App\Enums\ModificationType::CHANGEMENT_CHEVAL)
                                                <span class="text-gray-400 line-through">{{ $mod->ancienCheval->nom ?? '-' }}</span>
                                                <span class="mx-1">&rarr;</span>
                                                <span class="text-green-700 font-medium">{{ $mod->nouveauCheval->nom ?? '-' }}</span>
                                                @if ($mod->nouveauCheval->num_sire ?? null)
                                                    <div class="text-xs text-gray-500">({{ $mod->nouveauCheval->num_sire }})</div>
                                                @endif
                                            @else
                                                {{ $mod->engagement->cheval->nom ?? '-' }}
                                                @if ($mod->engagement->cheval->num_sire ?? null)
                                                    <div class="text-xs text-gray-500">({{ $mod->engagement->cheval->num_sire }})</div>
                                                @endif
                                            @endif
                                        </td>
                                        {{-- Compte (numero de compte) --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            @if ($mod->type_compte)
                                                {{ $mod->type_compte }}
                                                @if ($mod->numero_compte)
                                                    <div class="text-xs text-gray-500">({{ $mod->numero_compte }})</div>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($mod->statut->value === 'en_attente')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                            @elseif ($mod->statut->value === 'fait')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Fait</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            @if ($mod->statut->value === 'en_attente')
                                                <div class="flex justify-end space-x-2">
                                                    @if ($mod->type->isPaid())
                                                        <button type="button" onclick="toggleEditRow({{ $mod->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Editer</button>
                                                    @endif
                                                    <form method="POST" action="{{ route('modifications.fait', $mod) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Fait</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('modifications.destroy', $mod) }}"
                                                        onsubmit="return confirm('Annuler cette modification ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Annuler</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- Inline edit row --}}
                                    @if ($mod->statut->value === 'en_attente' && $mod->type->isPaid())
                                        <tr id="edit-row-{{ $mod->id }}" class="hidden bg-gray-50">
                                            <td colspan="8" class="px-4 py-4">
                                                <form method="POST" action="{{ route('modifications.update-paiement', $mod) }}" class="space-y-4">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                        {{-- Type de compte --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Type de compte</label>
                                                            <select name="type_compte" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                                <option value="">—</option>
                                                                <option value="Licence" {{ $mod->type_compte === 'Licence' ? 'selected' : '' }}>Licence</option>
                                                                <option value="Compte" {{ $mod->type_compte === 'Compte' ? 'selected' : '' }}>Compte</option>
                                                                <option value="Club" {{ $mod->type_compte === 'Club' ? 'selected' : '' }}>Club</option>
                                                            </select>
                                                        </div>
                                                        {{-- Numero de compte --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Numero de compte</label>
                                                            <input type="text" name="numero_compte" value="{{ $mod->numero_compte }}"
                                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                        </div>
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
                                                                    <span class="ml-1">Especes</span>
                                                                </label>
                                                                <label class="inline-flex items-center text-sm">
                                                                    <input type="checkbox" name="paiement_cheque" value="1" {{ $mod->paiement_cheque ? 'checked' : '' }}
                                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                    <span class="ml-1">Cheque</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-end">
                                                        <button type="button" onclick="toggleEditRow({{ $mod->id }})" class="mr-3 text-sm text-gray-600 hover:text-gray-800">Fermer</button>
                                                        <button type="submit"
                                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                                            Enregistrer
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleEditRow(modId) {
            const row = document.getElementById('edit-row-' + modId);
            if (row) {
                row.classList.toggle('hidden');
            }
        }

        function changementCheval() {
            const epreuves = @json($epreuvesJson);

            return {
                open: false,
                epreuveId: '',
                engagementId: '',
                cavaliers: [],
                filteredCavaliers: [],
                searchCavalier: '',
                showCavalierList: false,
                selectedCavalierLabel: '',

                searchCheval: '',
                resultatsChevaux: [],
                nouveauChevalId: '',
                selectedChevalNom: '',
                selectedChevalSire: '',
                showResults: false,

                isNouveauCheval: false,
                nouveauChevalNom: '',
                nouveauChevalSire: '',

                onEpreuveChange() {
                    this.engagementId = '';
                    this.selectedCavalierLabel = '';
                    this.searchCavalier = '';
                    this.resetCheval();
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    this.cavaliers = ep ? ep.engagements : [];
                    this.filteredCavaliers = this.cavaliers;
                },

                filterCavaliers() {
                    this.showCavalierList = true;
                    const q = this.searchCavalier.toLowerCase();
                    if (!q) {
                        this.filteredCavaliers = this.cavaliers;
                        return;
                    }
                    this.filteredCavaliers = this.cavaliers.filter(c =>
                        c.cavalier_nom.toLowerCase().includes(q) ||
                        c.cavalier_prenom.toLowerCase().includes(q) ||
                        c.cheval_nom.toLowerCase().includes(q) ||
                        (c.numero_depart && c.numero_depart.toString().includes(q))
                    );
                },

                selectCavalier(c) {
                    this.engagementId = c.engagement_id;
                    this.searchCavalier = '';
                    this.showCavalierList = false;
                    const numDepart = c.numero_depart ? `N°${c.numero_depart} - ` : '';
                    this.selectedCavalierLabel = `${numDepart}${c.cavalier_nom} ${c.cavalier_prenom} — Cheval: ${c.cheval_nom}`;
                    this.resetCheval();
                },

                resetCheval() {
                    this.nouveauChevalId = '';
                    this.selectedChevalNom = '';
                    this.selectedChevalSire = '';
                    this.searchCheval = '';
                    this.resultatsChevaux = [];
                    this.showResults = false;
                    this.nouveauChevalNom = '';
                    this.nouveauChevalSire = '';
                },

                async searchChevaux() {
                    if (this.searchCheval.length < 2) {
                        this.resultatsChevaux = [];
                        return;
                    }
                    const res = await fetch(`/api/chevaux/search?q=${encodeURIComponent(this.searchCheval)}`);
                    this.resultatsChevaux = await res.json();
                    this.showResults = true;
                },

                selectCheval(ch) {
                    this.nouveauChevalId = ch.id;
                    this.selectedChevalNom = ch.nom;
                    this.selectedChevalSire = ch.num_sire || '';
                    this.searchCheval = ch.nom;
                    this.showResults = false;
                    this.resultatsChevaux = [];
                }
            };
        }

        function invitationForm() {
            const epreuves = @json($epreuvesJson);
            const isGrandNational = @json($concours->grand_national);

            return {
                open: false,
                epreuveId: '',
                epreuvePrix: 0,
                epreuveTypeDetecte: null,

                cavalierMode: 'existing',
                cavalierId: '',
                searchCavalier: '',
                showCavalierList: false,
                selectedCavalierLabel: '',
                filteredCavaliers: [],
                allCavaliers: [],
                nouveauCavalierNom: '',
                nouveauCavalierPrenom: '',
                nouveauCavalierLicence: '',

                chevalId: '',
                searchCheval: '',
                chevalResults: [],
                showChevalResults: false,
                selectedChevalNom: '',
                isNouveauCheval: false,
                nouveauChevalNom: '',
                nouveauChevalSire: '',

                isGn: false,

                typeCompte: '',
                numeroCompte: '',

                paiementCheque: false,

                facture: '0',
                nomFacturation: '',
                telephone: '',
                emailFacturation: '',
                adresseFacturation: '',
                clientsResultats: [],
                showClientsResults: false,

                get calculatedPrix() {
                    const base = this.epreuvePrix || 0;
                    if (isGrandNational && this.isGn && this.epreuveTypeDetecte === 'pro') {
                        return base;
                    }
                    return base + 15;
                },

                get calculatedPf() {
                    if (isGrandNational && this.isGn && this.epreuveTypeDetecte === 'pro') {
                        return 4.80;
                    }
                    return 14.40;
                },

                onEpreuveChange() {
                    this.cavalierId = '';
                    this.selectedCavalierLabel = '';
                    this.searchCavalier = '';
                    this.resetCheval();
                    this.typeCompte = '';
                    this.numeroCompte = '';
                    this.isGn = false;
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    if (ep) {
                        this.epreuvePrix = parseFloat(ep.prix) || 0;
                        this.epreuveTypeDetecte = ep.type_detecte;
                        const seen = new Set();
                        this.allCavaliers = ep.engagements.filter(eng => {
                            if (!eng.cavalier_id || seen.has(eng.cavalier_id)) return false;
                            seen.add(eng.cavalier_id);
                            return true;
                        });
                        this.filteredCavaliers = this.allCavaliers;
                    } else {
                        this.epreuvePrix = 0;
                        this.epreuveTypeDetecte = null;
                        this.allCavaliers = [];
                        this.filteredCavaliers = [];
                    }
                },

                filterCavaliers() {
                    this.showCavalierList = true;
                    const q = this.searchCavalier.toLowerCase();
                    if (!q) {
                        this.filteredCavaliers = this.allCavaliers;
                        return;
                    }
                    this.filteredCavaliers = this.allCavaliers.filter(c =>
                        c.cavalier_nom.toLowerCase().includes(q) ||
                        c.cavalier_prenom.toLowerCase().includes(q)
                    );
                },

                selectCavalier(c) {
                    this.cavalierId = c.cavalier_id;
                    this.searchCavalier = '';
                    this.showCavalierList = false;
                    this.selectedCavalierLabel = `${c.cavalier_nom} ${c.cavalier_prenom}`;
                },

                resetCheval() {
                    this.chevalId = '';
                    this.selectedChevalNom = '';
                    this.searchCheval = '';
                    this.chevalResults = [];
                    this.showChevalResults = false;
                    this.nouveauChevalNom = '';
                    this.nouveauChevalSire = '';
                },

                async searchChevaux() {
                    if (this.searchCheval.length < 2) {
                        this.chevalResults = [];
                        return;
                    }
                    const res = await fetch(`/api/chevaux/search?q=${encodeURIComponent(this.searchCheval)}`);
                    this.chevalResults = await res.json();
                    this.showChevalResults = true;
                },

                selectCheval(ch) {
                    this.chevalId = ch.id;
                    this.selectedChevalNom = ch.nom;
                    this.searchCheval = ch.nom;
                    this.showChevalResults = false;
                    this.chevalResults = [];
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
                }
            };
        }

        function nonPartantForm() {
            const epreuves = @json($epreuvesJson);

            return {
                open: false,
                epreuveId: '',
                engagementId: '',
                cavaliers: [],
                filteredCavaliers: [],
                searchCavalier: '',
                showCavalierList: false,
                selectedCavalierLabel: '',

                onEpreuveChange() {
                    this.engagementId = '';
                    this.selectedCavalierLabel = '';
                    this.searchCavalier = '';
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    this.cavaliers = ep ? ep.engagements.filter(eng => !eng.is_non_partant) : [];
                    this.filteredCavaliers = this.cavaliers;
                },

                filterCavaliers() {
                    this.showCavalierList = true;
                    const q = this.searchCavalier.toLowerCase();
                    if (!q) {
                        this.filteredCavaliers = this.cavaliers;
                        return;
                    }
                    this.filteredCavaliers = this.cavaliers.filter(c =>
                        c.cavalier_nom.toLowerCase().includes(q) ||
                        c.cavalier_prenom.toLowerCase().includes(q) ||
                        c.cheval_nom.toLowerCase().includes(q)
                    );
                },

                selectCavalier(c) {
                    this.engagementId = c.engagement_id;
                    this.searchCavalier = '';
                    this.showCavalierList = false;
                    const numDepart = c.numero_depart ? `N°${c.numero_depart} - ` : '';
                    this.selectedCavalierLabel = `${numDepart}${c.cavalier_nom} ${c.cavalier_prenom} — ${c.cheval_nom}`;
                }
            };
        }
    </script>
</x-app-layout>
