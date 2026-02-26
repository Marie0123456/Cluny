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

            @include('concours.partials.tabs', ['active' => 'modifications'])

            <!-- Changement de cheval form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6"
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
                    <div x-show="cavaliers.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cavalier (et cheval actuel)</label>
                        <select x-model="engagementId"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choisir le cavalier</option>
                            <template x-for="c in cavaliers" :key="c.engagement_id">
                                <option :value="c.engagement_id"
                                    x-text="`${c.cavalier_nom} ${c.cavalier_prenom} — Cheval actuel: ${c.cheval_nom}`">
                                </option>
                            </template>
                        </select>
                    </div>

                    <!-- Step 3: Nouveau cheval (autocomplete) -->
                    <div x-show="engagementId" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau cheval</label>
                        <input type="text" x-model="searchCheval"
                            @input.debounce.300ms="searchChevaux()"
                            @focus="showResults = true"
                            placeholder="Rechercher un cheval..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                        <div x-show="selectedChevalNom" class="mt-1 text-sm text-green-700 font-medium">
                            Cheval selectionne : <span x-text="selectedChevalNom"></span>
                        </div>

                        <ul x-show="showResults && resultatsChevaux.length > 0"
                            @click.away="showResults = false"
                            class="absolute z-10 w-full bg-white border border-gray-200 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto">
                            <template x-for="ch in resultatsChevaux" :key="ch.id">
                                <li @click="selectCheval(ch)"
                                    class="cursor-pointer hover:bg-indigo-50 px-4 py-2 text-sm">
                                    <span x-text="ch.nom" class="font-medium"></span>
                                    <span x-show="ch.race" x-text="' — ' + ch.race" class="text-gray-500"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Submit -->
                    <div x-show="nouveauChevalId">
                        <form method="POST" action="{{ route('concours.modifications.changement-cheval', $concours) }}">
                            @csrf
                            <input type="hidden" name="engagement_id" :value="engagementId">
                            <input type="hidden" name="nouveau_cheval_id" :value="nouveauChevalId">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Valider le changement
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modifications table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @php
                    $visibleMods = $modifications->where('statut', '!=', 'supprime');
                @endphp

                @if ($visibleMods->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune modification pour le moment.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Epreuve</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dep.</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ancien cheval</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nouveau cheval</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($visibleMods->sortBy(fn($m) => $m->statut === 'en_attente' ? 0 : 1) as $mod)
                                <tr class="{{ $mod->statut === 'fait' ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        {{ $mod->engagement->epreuve->numero ?? '-' }} - {{ $mod->engagement->epreuve->nom ?? '' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $mod->engagement->numero_depart ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 font-medium">
                                        {{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-400 line-through">
                                        {{ $mod->ancienCheval->nom ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-green-700 font-medium">
                                        {{ $mod->nouveauCheval->nom ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if ($mod->statut === 'en_attente')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                        @elseif ($mod->statut === 'fait')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Fait</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        @if ($mod->statut === 'en_attente')
                                            <div class="flex justify-end space-x-2">
                                                <form method="POST" action="{{ route('modifications.fait', $mod) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Fait</button>
                                                </form>
                                                <form method="POST" action="{{ route('modifications.destroy', $mod) }}"
                                                    onsubmit="return confirm('Annuler cette modification et restaurer l\'ancien cheval ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Annuler</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <script>
        function changementCheval() {
            const epreuves = @json($epreuves->map(fn($e) => [
                'id' => $e->id,
                'engagements' => $e->engagements->map(fn($eng) => [
                    'engagement_id' => $eng->id,
                    'cavalier_nom' => $eng->cavalier?->nom ?? '',
                    'cavalier_prenom' => $eng->cavalier?->prenom ?? '',
                    'cheval_nom' => $eng->cheval?->nom ?? '',
                ])
            ]));

            return {
                open: false,
                epreuveId: '',
                engagementId: '',
                cavaliers: [],
                searchCheval: '',
                resultatsChevaux: [],
                nouveauChevalId: '',
                selectedChevalNom: '',
                showResults: false,

                onEpreuveChange() {
                    this.engagementId = '';
                    this.nouveauChevalId = '';
                    this.selectedChevalNom = '';
                    this.searchCheval = '';
                    const ep = epreuves.find(e => e.id == this.epreuveId);
                    this.cavaliers = ep ? ep.engagements : [];
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
                    this.searchCheval = ch.nom;
                    this.showResults = false;
                    this.resultatsChevaux = [];
                }
            };
        }
    </script>
</x-app-layout>
