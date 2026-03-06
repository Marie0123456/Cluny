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

            <!-- Barre de boutons modifications -->
            <div x-data="{ activeForm: '' }">
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-3">
                        <button @click="activeForm = activeForm === 'cheval' ? '' : 'cheval'"
                            :class="activeForm === 'cheval' ? 'bg-indigo-700 ring-2 ring-indigo-300' : 'bg-indigo-600'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Chgt Cheval
                        </button>
                        <button @click="activeForm = activeForm === 'cavalier' ? '' : 'cavalier'"
                            :class="activeForm === 'cavalier' ? 'bg-purple-700 ring-2 ring-purple-300' : 'bg-purple-600'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                            Chgt Cavalier
                        </button>
                        <button @click="activeForm = activeForm === 'invitation' ? '' : 'invitation'"
                            :class="activeForm === 'invitation' ? 'bg-blue-700 ring-2 ring-blue-300' : 'bg-blue-600'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                            Invitation
                        </button>
                        <button @click="activeForm = activeForm === 'epreuve' ? '' : 'epreuve'"
                            :class="activeForm === 'epreuve' ? 'bg-yellow-700 ring-2 ring-yellow-300' : 'bg-yellow-600'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 transition">
                            Chgt Épreuve
                        </button>
                        <button @click="activeForm = activeForm === 'np' ? '' : 'np'"
                            :class="activeForm === 'np' ? 'bg-gray-700 ring-2 ring-gray-300' : 'bg-gray-600'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Déclarer NP
                        </button>
                    </div>
                </div>

                <!-- Changement de cheval form -->
                <div x-show="activeForm === 'cheval'" x-transition x-cloak>
                    <div x-data="changementCheval()" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 space-y-4">
                    <!-- Step 1: Epreuve -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Épreuve</label>
                        <select x-model="epreuveId" @change="onEpreuveChange()"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choisir l'épreuve</option>
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
                            placeholder="Nom, cheval ou N° de départ..."
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
                                placeholder="Rechercher un cheval du concours par nom (min. 2 lettres)..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            <div x-show="chevalSearchLoading" class="mt-1 text-sm text-gray-400">Recherche...</div>
                            <div x-show="chevalSearchDone && resultatsChevaux.length === 0 && searchCheval.length >= 2 && !chevalSearchLoading && !selectedChevalNom"
                                class="mt-1 text-sm text-orange-600">
                                Aucun cheval du concours ne correspond. Cochez « Nouveau cheval » et renseignez le numéro de SIRE.
                            </div>

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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de SIRE</label>
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

                <!-- Changement de cavalier form -->
                <div x-show="activeForm === 'cavalier'" x-transition x-cloak>
                    <div x-data="changementCavalier()" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 space-y-4">
                    @if ($concours->grand_national)
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md text-sm text-yellow-800">
                            Concours Grand National : le changement de cavalier n'est pas autorisé sur les épreuves Pro.
                        </div>
                    @endif

                    <!-- Step 1: Epreuve -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Épreuve</label>
                        <select x-model="epreuveId" @change="onEpreuveChange()"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choisir l'épreuve</option>
                            @foreach($epreuves as $epreuve)
                                <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                            @endforeach
                        </select>
                        <p x-show="proBlocked" class="mt-1 text-sm text-red-600">Épreuve Pro : changement de cavalier non autorisé en GN.</p>
                    </div>

                    <!-- Step 2: Engagement (cavalier actuel) -->
                    <div x-show="cavaliers.length > 0 && !proBlocked" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cavalier actuel (et cheval)</label>
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

                    <!-- Step 3: Nouveau cavalier -->
                    <div x-show="engagementId && !proBlocked">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau cavalier</label>

                        <label class="inline-flex items-center mb-3 cursor-pointer">
                            <input type="checkbox" x-model="isNouveauCavalier" @change="resetNouveauCavalier()"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Nouveau cavalier (pas encore dans la base)</span>
                        </label>

                        <div x-show="!isNouveauCavalier" class="relative">
                            <input type="text" x-model="searchNouveauCavalier"
                                @input.debounce.150ms="searchCavalierApi()"
                                @focus="showNouveauResults = true"
                                placeholder="Rechercher un cavalier par nom..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            <div x-show="selectedNouveauLabel" class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <span x-text="selectedNouveauLabel"></span>
                                </span>
                                <button type="button" @click="resetNouveauCavalier()" class="text-gray-400 hover:text-red-500 text-sm">&times;</button>
                            </div>

                            <ul x-show="showNouveauResults && cavalierApiResults.length > 0"
                                @click.away="showNouveauResults = false"
                                class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 max-h-64 overflow-y-auto divide-y divide-gray-100">
                                <template x-for="cav in cavalierApiResults" :key="cav.id">
                                    <li @click="selectNouveauCavalier(cav)"
                                        class="cursor-pointer hover:bg-indigo-50 px-4 py-3 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-900" x-text="`${cav.nom} ${cav.prenom}`"></span>
                                            <span x-show="cav.num_licence" class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded" x-text="`Lic: ${cav.num_licence}`"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="isNouveauCavalier" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                                <input type="text" x-model="nouveauCavalierNom"
                                    placeholder="Nom du cavalier"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                                <input type="text" x-model="nouveauCavalierPrenom"
                                    placeholder="Prénom"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">N° de licence</label>
                                <input type="text" x-model="nouveauCavalierLicence"
                                    placeholder="Ex: 1234567"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div x-show="(nouveauCavalierId || (isNouveauCavalier && nouveauCavalierNom)) && !proBlocked">
                        <form method="POST" action="{{ route('concours.modifications.changement-cavalier', $concours) }}">
                            @csrf
                            <input type="hidden" name="engagement_id" :value="engagementId">
                            <template x-if="!isNouveauCavalier">
                                <input type="hidden" name="nouveau_cavalier_id" :value="nouveauCavalierId">
                            </template>
                            <template x-if="isNouveauCavalier">
                                <div>
                                    <input type="hidden" name="nouveau_cavalier_nom" :value="nouveauCavalierNom">
                                    <input type="hidden" name="nouveau_cavalier_prenom" :value="nouveauCavalierPrenom">
                                    <input type="hidden" name="nouveau_cavalier_num_licence" :value="nouveauCavalierLicence">
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
                <div x-show="activeForm === 'invitation'" x-transition x-cloak>
                    <div x-data="invitationForm()" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <form method="POST" action="{{ route('concours.modifications.invitation', $concours) }}" class="space-y-4">
                        @csrf

                        <!-- Step 1: Epreuve -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Épreuve</label>
                            <select x-model="epreuveId" @change="onEpreuveChange()" name="epreuve_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir l'épreuve</option>
                                @foreach($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                                @endforeach
                            </select>
                            <div x-show="epreuveId" class="mt-2 text-sm">
                                <span class="text-gray-600">Prix épreuve :</span>
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
                                    <span class="text-sm text-gray-700">Cavalier du concours</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" x-model="cavalierMode" value="new"
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Nouveau cavalier (pas engagé sur le concours)</span>
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
                                    <template x-for="c in filteredCavaliers" :key="c.cavalier_id">
                                        <li @click="selectCavalier(c)"
                                            class="cursor-pointer hover:bg-indigo-50 px-4 py-3 border-b border-gray-100">
                                            <span class="font-semibold text-sm text-gray-900" x-text="`${c.cavalier_nom} ${c.cavalier_prenom}`"></span>
                                            <span x-show="c.num_licence" class="ml-2 text-xs text-gray-500" x-text="`(${c.num_licence})`"></span>
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
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                                        <input type="text" name="nouveau_cavalier_prenom" x-model="nouveauCavalierPrenom"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de licence</label>
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
                                    placeholder="Rechercher un cheval par nom (min. 2 lettres)..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="hidden" name="cheval_id" :value="chevalId">

                                <div x-show="chevalSearchLoading" class="mt-1 text-sm text-gray-400">Recherche...</div>
                                <div x-show="chevalSearchDone && chevalResults.length === 0 && searchCheval.length >= 2 && !chevalSearchLoading && !selectedChevalNom"
                                    class="mt-1 text-sm text-orange-600">
                                    Aucun cheval du concours ne correspond. Cochez « Nouveau cheval » et renseignez le numéro de SIRE.
                                </div>

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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de SIRE</label>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de compte</label>
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
                                    <p class="text-sm text-gray-600">Part Fédérale :</p>
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
                                    <span class="text-sm text-gray-700">Espèces</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="paiement_cheque" value="1" x-model="paiementCheque"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Chèque</span>
                                </label>
                            </div>
                        </div>

                        <!-- Numéro chèque -->
                        <div x-show="paiementCheque" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de chèque</label>
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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
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

                <!-- Changement d'epreuve form -->
                <div x-show="activeForm === 'epreuve'" x-transition x-cloak>
                    <div x-data="changementEpreuveForm()" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 space-y-4">
                    <form method="POST" action="{{ route('concours.modifications.changement-epreuve', $concours) }}" class="space-y-4">
                        @csrf

                        <!-- Step 1: Épreuve d'origine -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Épreuve d'origine</label>
                            <select x-model="epreuveId" @change="onEpreuveChange()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir l'épreuve</option>
                                @foreach($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Step 2: Cavalier / Engagement -->
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

                        <!-- GN checkbox (if concours is GN) -->
                        @if ($concours->grand_national)
                            <div x-show="engagementId" class="flex items-center gap-2">
                                <input type="checkbox" name="is_gn" value="1" x-model="isGn" @change="recalculatePrix()"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label class="text-sm font-medium text-gray-700">Cavalier GN</label>
                            </div>
                        @endif

                        <!-- Step 3: Nouvelle épreuve -->
                        <div x-show="engagementId">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nouvelle épreuve</label>
                            <select x-model="nouvelleEpreuveId" name="nouvelle_epreuve_id" @change="onNouvelleEpreuveChange()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir la nouvelle épreuve</option>
                                @foreach($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Step 4: Prix display (editable) -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId" class="p-4 bg-indigo-50 rounded-lg">
                            <div class="grid grid-cols-3 gap-4 items-end">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Prix</label>
                                    <input type="number" step="0.01" min="0" x-model.number="editablePrix" name="prix"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-400" x-text="'Diff: ' + Math.max(nouvelleEpreuvePrix - epreuvePrix, 0).toFixed(2) + ' + 15€'"></p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">dont PF (inclus)</label>
                                    <input type="number" step="0.01" min="0" x-model.number="editablePf" name="pf"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">TOTAL</label>
                                    <div class="text-lg font-bold text-indigo-900" x-text="editablePrix.toFixed(2) + ' €'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Type de compte + numero -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de compte</label>
                                <select name="type_compte"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choisir</option>
                                    <option value="Licence">Licence</option>
                                    <option value="Compte">Compte</option>
                                    <option value="Club">Club</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de compte</label>
                                <input type="text" name="numero_compte"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Step 6: Jour de paiement -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jour de paiement (optionnel)</label>
                            <input type="date" name="jour_paiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Step 7: Moyen de paiement -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId">
                            <span class="block text-sm font-medium text-gray-700 mb-2">Moyen de paiement</span>
                            <div class="flex gap-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="paiement_cb" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">CB</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="paiement_especes" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Espèces</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="paiement_cheque" value="1" x-model="paiementCheque"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Chèque</span>
                                </label>
                            </div>
                        </div>

                        <!-- Numéro chèque -->
                        <div x-show="paiementCheque && nouvelleEpreuveId && nouvelleEpreuveId != epreuveId">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de chèque</label>
                            <input type="text" name="numero_cheque"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Step 8: Facture -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId">
                            <span class="block text-sm font-medium text-gray-700 mb-2">Facture</span>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="facture" value="1" x-model="facture"
                                        class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Oui</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="facture" value="0" x-model="facture"
                                        class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Non</span>
                                </label>
                            </div>
                        </div>

                        <!-- Facturation details -->
                        <div x-show="facture === '1' && nouvelleEpreuveId && nouvelleEpreuveId != epreuveId" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de facturation</label>
                                <input type="text" name="nom_facturation" x-model="nomFacturation"
                                    @input.debounce.300ms="searchClients()"
                                    @focus="showClientsResults = true"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <ul x-show="showClientsResults && clientsResultats.length > 0"
                                    @click.away="showClientsResults = false"
                                    class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto divide-y divide-gray-100">
                                    <template x-for="client in clientsResultats" :key="client.id">
                                        <li @click="selectClient(client)"
                                            class="cursor-pointer hover:bg-indigo-50 px-4 py-2 text-sm" x-text="client.nom"></li>
                                    </template>
                                </ul>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                <input type="text" name="telephone" x-model="telephone"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" x-model="emailFacturation"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                                <textarea name="adresse" x-model="adresseFacturation" rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div x-show="nouvelleEpreuveId && nouvelleEpreuveId != epreuveId && engagementId">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Valider le changement d'épreuve
                            </button>
                        </div>
                    </form>
                    </div>
                </div>

                <!-- Non-partant form -->
                <div x-show="activeForm === 'np'" x-transition x-cloak>
                    <div x-data="nonPartantForm()" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 space-y-4">
                    <form method="POST" action="{{ route('concours.modifications.non-partant', $concours) }}" class="space-y-4">
                        @csrf

                        <!-- Epreuve -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Épreuve</label>
                            <select x-model="epreuveId" @change="onEpreuveChange()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choisir l'épreuve</option>
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
                                placeholder="Nom, cheval ou N° de départ..."
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
                                Déclarer non-partant
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <!-- Modifications table -->
            <div class="bg-white shadow-sm sm:rounded-lg" x-data="modificationsFilter()" x-cloak>
                @php
                    $visibleMods = $modifications->filter(fn($m) => $m->statut->value !== 'supprime');
                @endphp

                @if ($visibleMods->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune modification pour le moment.
                    </div>
                @else
                    <!-- Filtres -->
                    <div class="px-4 pt-4 pb-2 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Épreuve</label>
                            <select x-model="filterEpreuve"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Toutes</option>
                                @php
                                    $modEpreuves = $visibleMods->map(fn($m) => $m->engagement->epreuve)->filter()->unique('id')->sortBy(fn($e) => intval($e->numero));
                                @endphp
                                @foreach ($modEpreuves as $ep)
                                    <option value="{{ $ep->numero }}">{{ $ep->numero }} - {{ $ep->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nom (cavalier)</label>
                            <input type="text" x-model="filterNom" placeholder="Nom du cavalier..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jour</label>
                            <input type="date" x-model="filterJour"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                            <select x-model="filterStatut"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                <option value="cree">Créé</option>
                                <option value="fait">Fait</option>
                                <option value="modifie">Modifié</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Paiement</label>
                            <select x-model="filterPaiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Tous</option>
                                <option value="cb">CB</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="sans">Sans paiement</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="filterEpreuve || filterNom || filterJour || filterStatut || filterPaiement" class="px-4 pb-2">
                        <button @click="resetFilters()" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Réinitialiser les filtres</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Épreuve</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Départ</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    @if ($concours->grand_national)
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Compte</th>
                                    @else
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    @endif
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($visibleMods as $mod)
                                    <tr class="{{ $mod->statut->value === 'fait' ? 'opacity-50' : '' }}"
                                        @php
                                        $paiementValues = [];
                                        if ($mod->paiement_cb) $paiementValues[] = 'cb';
                                        if ($mod->paiement_especes) $paiementValues[] = 'especes';
                                        if ($mod->paiement_cheque) $paiementValues[] = 'cheque';
                                    @endphp
                                    x-show="showRow({{ json_encode([
                                            'epreuve' => (string) ($mod->engagement->epreuve->numero ?? ''),
                                            'nom' => $mod->type === \App\Enums\ModificationType::CHANGEMENT_CAVALIER
                                                ? trim(($mod->ancienCavalier->nom ?? '') . ' ' . ($mod->ancienCavalier->prenom ?? '') . ' ' . ($mod->nouveauCavalier->nom ?? '') . ' ' . ($mod->nouveauCavalier->prenom ?? ''))
                                                : trim(($mod->engagement->cavalier->nom ?? '') . ' ' . ($mod->engagement->cavalier->prenom ?? '')),
                                            'jour' => $mod->created_at->format('Y-m-d'),
                                            'statut' => $mod->statut->value,
                                            'paiement' => $paiementValues,
                                        ]) }})">
                                        {{-- Type --}}
                                        <td class="px-4 py-2 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        {{-- Epreuve --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            @if ($mod->type === \App\Enums\ModificationType::CHANGEMENT_EPREUVE && $mod->linkedModification)
                                                <span class="text-gray-400">{{ $mod->linkedModification->engagement->epreuve->numero ?? '?' }}</span>
                                                <span class="mx-1">&rarr;</span>
                                                <span class="font-medium">{{ $mod->engagement->epreuve->numero ?? '-' }}</span>
                                            @else
                                                {{ $mod->engagement->epreuve->numero ?? '-' }}
                                            @endif
                                        </td>
                                        {{-- Depart --}}
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            @if ($mod->type === \App\Enums\ModificationType::NON_PARTANT)
                                                {{ $mod->engagement->numero_depart ?? '-' }} <span class="font-bold text-red-600">NP</span>
                                            @elseif ($mod->type === \App\Enums\ModificationType::CHANGEMENT_EPREUVE && $mod->linkedModification)
                                                <span class="text-gray-400">{{ $mod->linkedModification->engagement->numero_depart ?? '?' }}</span>
                                                <span class="mx-1">&rarr;</span>
                                                <span class="font-medium text-gray-900">{{ $mod->engagement->numero_depart ?? '-' }}</span>
                                            @else
                                                {{ $mod->engagement->numero_depart ?? '-' }}
                                            @endif
                                        </td>
                                        {{-- Nom (numero licence) --}}
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            @if ($mod->type === \App\Enums\ModificationType::CHANGEMENT_CAVALIER)
                                                <span class="text-gray-400 line-through">{{ $mod->ancienCavalier->nom ?? '-' }} {{ $mod->ancienCavalier->prenom ?? '' }}</span>
                                                <span class="mx-1">&rarr;</span>
                                                <span class="text-green-700 font-medium">{{ $mod->nouveauCavalier->nom ?? '-' }} {{ $mod->nouveauCavalier->prenom ?? '' }}</span>
                                                @if ($mod->nouveauCavalier->num_licence ?? null)
                                                    <div class="text-xs text-gray-500">({{ $mod->nouveauCavalier->num_licence }})</div>
                                                @endif
                                            @else
                                                <span class="font-medium">{{ $mod->engagement->cavalier->nom ?? '' }} {{ $mod->engagement->cavalier->prenom ?? '' }}</span>
                                                @if ($mod->is_gn)
                                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">GN</span>
                                                @endif
                                                @if ($mod->engagement->cavalier->num_licence ?? null)
                                                    <div class="text-xs text-gray-500">({{ $mod->engagement->cavalier->num_licence }})</div>
                                                @endif
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
                                        @if ($concours->grand_national)
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
                                        @else
                                            {{-- Prix --}}
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                @if ($mod->type->isPaid())
                                                    {{ number_format((float) $mod->prix, 2, ',', ' ') }} &euro;
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            {{-- Moyen de paiement --}}
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                @php
                                                    $moyens = [];
                                                    if ($mod->paiement_cb) $moyens[] = 'CB';
                                                    if ($mod->paiement_especes) $moyens[] = 'Espèces';
                                                    if ($mod->paiement_cheque) $moyens[] = 'Chèque';
                                                @endphp
                                                @if (count($moyens) > 0)
                                                    {{ implode(', ', $moyens) }}
                                                    @if ($mod->paiement_cheque && $mod->numero_cheque)
                                                        <div class="text-xs text-gray-500">(N°{{ $mod->numero_cheque }})</div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-2 text-sm">
                                            @if ($mod->statut->value === 'cree')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Créé</span>
                                            @elseif ($mod->statut->value === 'fait')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Fait</span>
                                            @elseif ($mod->statut->value === 'modifie')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Modifié</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            <div class="flex justify-end space-x-2">
                                                @if (in_array($mod->statut->value, ['cree', 'fait']) && $mod->type->isPaid())
                                                    <button type="button" onclick="toggleEditRow({{ $mod->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Editer</button>
                                                @endif
                                                @if (in_array($mod->statut->value, ['cree', 'modifie']))
                                                    <form method="POST" action="{{ route('modifications.fait', $mod) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Fait</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('modifications.destroy', $mod) }}"
                                                    onsubmit="return confirm('Supprimer cette modification ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Supprimer</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Inline edit row --}}
                                    @if (in_array($mod->statut->value, ['cree', 'fait']) && $mod->type->isPaid())
                                        <tr id="edit-row-{{ $mod->id }}" class="hidden bg-gray-50">
                                            <td colspan="{{ $concours->grand_national ? 8 : 9 }}" class="px-4 py-4">
                                                <form method="POST" action="{{ route('modifications.update-paiement', $mod) }}" class="space-y-4"
                                                    x-data="{ paiementCheque: {{ $mod->paiement_cheque ? 'true' : 'false' }}, facture: '{{ $mod->facture ? '1' : '0' }}' }">
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
                                                        {{-- Numéro de compte --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Numéro de compte</label>
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
                                                                    <span class="ml-1">Espèces</span>
                                                                </label>
                                                                <label class="inline-flex items-center text-sm">
                                                                    <input type="checkbox" name="paiement_cheque" value="1" x-model="paiementCheque"
                                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                    <span class="ml-1">Chèque</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Numéro de chèque --}}
                                                    <div x-show="paiementCheque" x-transition class="max-w-xs">
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">Numéro de chèque</label>
                                                        <input type="text" name="numero_cheque" value="{{ $mod->numero_cheque }}"
                                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                    </div>

                                                    {{-- Facture --}}
                                                    <div class="space-y-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Facture</label>
                                                            <div class="flex gap-4">
                                                                <label class="inline-flex items-center text-sm">
                                                                    <input type="radio" name="facture" value="1" x-model="facture"
                                                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                    <span class="ml-1">Oui</span>
                                                                </label>
                                                                <label class="inline-flex items-center text-sm">
                                                                    <input type="radio" name="facture" value="0" x-model="facture"
                                                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                    <span class="ml-1">Non</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div x-show="facture == 1" x-transition class="grid grid-cols-2 md:grid-cols-4 gap-4 p-3 bg-white rounded-lg border border-gray-200">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Nom de facturation</label>
                                                                <input type="text" name="nom_facturation" value="{{ $mod->clientFacturation?->nom }}"
                                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Téléphone</label>
                                                                <input type="text" name="telephone" value="{{ $mod->clientFacturation?->telephone }}"
                                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                                                                <input type="email" name="email" value="{{ $mod->clientFacturation?->email }}"
                                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Adresse</label>
                                                                <input type="text" name="adresse" value="{{ $mod->clientFacturation?->adresse }}"
                                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
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
        const __epreuves = @json($epreuvesJson);
        const __isGrandNational = @json($concours->grand_national);
        const __allCavaliersConcours = @json($allCavaliersJson);

        function modificationsFilter() {
            return {
                filterEpreuve: '',
                filterNom: '',
                filterJour: '',
                filterStatut: '',
                filterPaiement: '',

                showRow(row) {
                    if (this.filterEpreuve && row.epreuve !== this.filterEpreuve) return false;
                    if (this.filterNom && !row.nom.toLowerCase().includes(this.filterNom.toLowerCase())) return false;
                    if (this.filterJour && row.jour !== this.filterJour) return false;
                    if (this.filterStatut && row.statut !== this.filterStatut) return false;
                    if (this.filterPaiement) {
                        if (this.filterPaiement === 'sans') {
                            if (row.paiement && row.paiement.length > 0) return false;
                        } else {
                            if (!row.paiement || !row.paiement.includes(this.filterPaiement)) return false;
                        }
                    }
                    return true;
                },

                resetFilters() {
                    this.filterEpreuve = '';
                    this.filterNom = '';
                    this.filterJour = '';
                    this.filterStatut = '';
                    this.filterPaiement = '';
                }
            };
        }

        function toggleEditRow(modId) {
            const row = document.getElementById('edit-row-' + modId);
            if (row) {
                row.classList.toggle('hidden');
            }
        }

        function changementCheval() {
            const epreuves = __epreuves;

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
                chevalSearchLoading: false,
                chevalSearchDone: false,

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
                    this.chevalSearchLoading = false;
                    this.chevalSearchDone = false;
                    this.nouveauChevalNom = '';
                    this.nouveauChevalSire = '';
                },

                async searchChevaux() {
                    if (this.searchCheval.length < 2) {
                        this.resultatsChevaux = [];
                        this.chevalSearchDone = false;
                        return;
                    }
                    this.chevalSearchLoading = true;
                    this.chevalSearchDone = false;
                    try {
                        const res = await fetch(`/api/chevaux/search?q=${encodeURIComponent(this.searchCheval)}&concours_id={{ $concours->getKey() }}`);
                        this.resultatsChevaux = await res.json();
                        this.showResults = true;
                    } catch (e) {
                        this.resultatsChevaux = [];
                        console.error('Erreur recherche cheval:', e);
                    }
                    this.chevalSearchLoading = false;
                    this.chevalSearchDone = true;
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

        function changementCavalier() {
            const epreuves = __epreuves;
            const isGrandNational = __isGrandNational;

            return {
                open: false,
                epreuveId: '',
                engagementId: '',
                cavaliers: [],
                filteredCavaliers: [],
                searchCavalier: '',
                showCavalierList: false,
                selectedCavalierLabel: '',
                proBlocked: false,

                isNouveauCavalier: false,
                nouveauCavalierId: '',
                nouveauCavalierNom: '',
                nouveauCavalierPrenom: '',
                nouveauCavalierLicence: '',
                searchNouveauCavalier: '',
                cavalierApiResults: [],
                showNouveauResults: false,
                selectedNouveauLabel: '',

                onEpreuveChange() {
                    this.engagementId = '';
                    this.selectedCavalierLabel = '';
                    this.searchCavalier = '';
                    this.resetNouveauCavalier();
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    this.cavaliers = ep ? ep.engagements.filter(eng => !eng.is_non_partant) : [];
                    this.filteredCavaliers = this.cavaliers;
                    this.proBlocked = isGrandNational && ep && ep.type_detecte === 'pro';
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
                    this.resetNouveauCavalier();
                },

                resetNouveauCavalier() {
                    this.nouveauCavalierId = '';
                    this.selectedNouveauLabel = '';
                    this.searchNouveauCavalier = '';
                    this.cavalierApiResults = [];
                    this.showNouveauResults = false;
                    this.nouveauCavalierNom = '';
                    this.nouveauCavalierPrenom = '';
                    this.nouveauCavalierLicence = '';
                },

                async searchCavalierApi() {
                    if (this.searchNouveauCavalier.length < 2) {
                        this.cavalierApiResults = [];
                        return;
                    }
                    const res = await fetch(`/api/cavaliers/search?q=${encodeURIComponent(this.searchNouveauCavalier)}`);
                    this.cavalierApiResults = await res.json();
                    this.showNouveauResults = true;
                },

                selectNouveauCavalier(cav) {
                    this.nouveauCavalierId = cav.id;
                    this.selectedNouveauLabel = `${cav.nom} ${cav.prenom}${cav.num_licence ? ' (Lic: ' + cav.num_licence + ')' : ''}`;
                    this.searchNouveauCavalier = `${cav.nom} ${cav.prenom}`;
                    this.showNouveauResults = false;
                    this.cavalierApiResults = [];
                }
            };
        }

        function changementEpreuveForm() {
            const epreuves = __epreuves;
            const isGrandNational = __isGrandNational;

            return {
                open: false,
                epreuveId: '',
                epreuvePrix: 0,
                epreuveTypeDetecte: null,
                engagementId: '',
                cavaliers: [],
                filteredCavaliers: [],
                searchCavalier: '',
                showCavalierList: false,
                selectedCavalierLabel: '',

                isGn: false,

                nouvelleEpreuveId: '',
                nouvelleEpreuvePrix: 0,
                nouvelleEpreuveTypeDetecte: null,

                editablePrix: 0,
                editablePf: 0,

                paiementCheque: false,
                facture: '0',
                nomFacturation: '',
                telephone: '',
                emailFacturation: '',
                adresseFacturation: '',
                clientsResultats: [],
                showClientsResults: false,

                onEpreuveChange() {
                    this.engagementId = '';
                    this.selectedCavalierLabel = '';
                    this.searchCavalier = '';
                    this.nouvelleEpreuveId = '';
                    this.editablePrix = 0;
                    this.editablePf = 0;
                    this.isGn = false;
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    if (ep) {
                        this.epreuvePrix = parseFloat(ep.prix) || 0;
                        this.epreuveTypeDetecte = ep.type_detecte;
                        this.cavaliers = ep.engagements.filter(eng => !eng.is_non_partant);
                        this.filteredCavaliers = this.cavaliers;
                    } else {
                        this.epreuvePrix = 0;
                        this.epreuveTypeDetecte = null;
                        this.cavaliers = [];
                        this.filteredCavaliers = [];
                    }
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
                    this.selectedCavalierLabel = `${numDepart}${c.cavalier_nom} ${c.cavalier_prenom} — ${c.cheval_nom}`;
                },

                onNouvelleEpreuveChange() {
                    const ep = epreuves.find(e => e.id == this.nouvelleEpreuveId);
                    if (ep) {
                        this.nouvelleEpreuvePrix = parseFloat(ep.prix) || 0;
                        this.nouvelleEpreuveTypeDetecte = ep.type_detecte;
                    } else {
                        this.nouvelleEpreuvePrix = 0;
                        this.nouvelleEpreuveTypeDetecte = null;
                    }
                    this.recalculatePrix();
                },

                recalculatePrix() {
                    const diff = this.nouvelleEpreuvePrix - this.epreuvePrix;

                    if (isGrandNational && this.isGn && this.nouvelleEpreuveTypeDetecte === 'pro') {
                        // GN + Pro : juste la différence (min 0), PF = 0
                        this.editablePrix = Math.max(diff, 0);
                        this.editablePf = 0;
                    } else {
                        // Cas normal : si diff négative → 0 + 15€, sinon diff + 15€
                        this.editablePrix = Math.max(diff, 0) + 15;
                        this.editablePf = 14.40;
                    }
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

        function invitationForm() {
            const epreuves = __epreuves;
            const isGrandNational = __isGrandNational;
            const allCavaliersConcours = __allCavaliersConcours;

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
                allCavaliers: allCavaliersConcours,
                nouveauCavalierNom: '',
                nouveauCavalierPrenom: '',
                nouveauCavalierLicence: '',

                chevalId: '',
                searchCheval: '',
                chevalResults: [],
                showChevalResults: false,
                selectedChevalNom: '',
                chevalSearchLoading: false,
                chevalSearchDone: false,
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
                    } else {
                        this.epreuvePrix = 0;
                        this.epreuveTypeDetecte = null;
                    }
                    this.filteredCavaliers = this.allCavaliers;
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
                    this.chevalSearchLoading = false;
                    this.chevalSearchDone = false;
                },

                async searchChevaux() {
                    if (this.searchCheval.length < 2) {
                        this.chevalResults = [];
                        this.chevalSearchDone = false;
                        return;
                    }
                    this.chevalSearchLoading = true;
                    this.chevalSearchDone = false;
                    try {
                        const res = await fetch(`/api/chevaux/search?q=${encodeURIComponent(this.searchCheval)}&concours_id={{ $concours->getKey() }}`);
                        this.chevalResults = await res.json();
                        this.showChevalResults = true;
                    } catch (e) {
                        this.chevalResults = [];
                        console.error('Erreur recherche cheval:', e);
                    }
                    this.chevalSearchLoading = false;
                    this.chevalSearchDone = true;
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
            const epreuves = __epreuves;

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
                        c.cheval_nom.toLowerCase().includes(q) ||
                        (c.numero_depart && c.numero_depart.toString().includes(q))
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
