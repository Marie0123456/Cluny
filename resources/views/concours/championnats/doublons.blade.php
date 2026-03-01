<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}
                &middot; {{ $concours->discipline->value }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('concours.partials.tabs', ['active' => 'championnats'])

            <div class="mb-4">
                <a href="{{ route('concours.championnats.index', $concours) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Retour aux championnats</a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900">Couples multi-championnats</h3>
                <p class="mt-1 text-sm text-gray-500">Pour chaque couple, cochez les championnats auxquels il participe. Les championnats d&eacute;coch&eacute;s apparaitront en orange/effac&eacute;.</p>
            </div>

            @if ($doublons->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">Aucun couple ne participe a plusieurs championnats.</p>
                </div>
            @else
                {{-- Filtres --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6" id="filtres">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Filtres</h4>
                    <div class="flex flex-col gap-4">
                        {{-- Filtre cavalier --}}
                        <div>
                            <label for="filtre-cavalier" class="block text-sm font-medium text-gray-700 mb-1">Cavalier</label>
                            <input type="text" id="filtre-cavalier" placeholder="Rechercher un cavalier..."
                                class="block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        {{-- Filtre championnats par prefixe --}}
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Championnats</span>
                            <div class="flex flex-wrap gap-2" id="filtre-championnats">
                                @php
                                    $prefixes = $championnats->map(function ($ch) {
                                        // Extract prefix: first word before space, or first 3+ uppercase letters
                                        if (preg_match('/^([A-Z]{2,})/', $ch->nom, $m)) {
                                            return $m[1];
                                        }
                                        return explode(' ', $ch->nom)[0];
                                    })->unique()->sort()->values();
                                @endphp
                                @foreach ($prefixes as $prefix)
                                    <button type="button" data-prefix="{{ $prefix }}" data-active="true"
                                        class="filtre-prefix-btn inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border transition
                                               bg-indigo-100 text-indigo-700 border-indigo-300 hover:bg-indigo-200">
                                        {{ $prefix }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('concours.championnats.doublons.store', $concours) }}" method="POST">
                    @csrf
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200" id="doublons-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cavalier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheval</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participe au championnat</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($doublons as $index => $doublon)
                                    @php
                                        $coupleKey = $doublon['cavalier_id'] . '-' . $doublon['cheval_id'];
                                        $hasAnyExclusion = false;
                                        foreach ($doublon['championnats'] as $ch) {
                                            if ($existingExclusions->has($coupleKey . '-' . $ch['id'])) {
                                                $hasAnyExclusion = true;
                                                break;
                                            }
                                        }
                                        $championnatNoms = collect($doublon['championnats'])->pluck('nom')->implode('|');
                                    @endphp
                                    <tr data-cavalier="{{ mb_strtolower($doublon['cavalier_prenom'] . ' ' . $doublon['cavalier_nom']) }}"
                                        data-championnats="{{ $championnatNoms }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 row-numero">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $doublon['cavalier_prenom'] }} {{ $doublon['cavalier_nom'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $doublon['club'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $doublon['cheval_nom'] }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <input type="hidden" name="couples[]" value="{{ $coupleKey }}">
                                            <div class="flex flex-col gap-1">
                                                @foreach ($doublon['championnats'] as $ch)
                                                    @php
                                                        $exclKey = $coupleKey . '-' . $ch['id'];
                                                        $isChecked = !$hasAnyExclusion || !$existingExclusions->has($exclKey);
                                                    @endphp
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox"
                                                            name="selections[{{ $coupleKey }}][]"
                                                            value="{{ $ch['id'] }}"
                                                            {{ $isChecked ? 'checked' : '' }}
                                                            class="h-4 w-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                        <span class="ml-2 text-sm text-gray-700">{{ $ch['nom'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Enregistrer les selections
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if ($doublons->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('filtre-cavalier');
            const prefixBtns = document.querySelectorAll('.filtre-prefix-btn');
            const rows = document.querySelectorAll('#doublons-table tbody tr');

            function getActivePrefixes() {
                const active = [];
                prefixBtns.forEach(btn => {
                    if (btn.dataset.active === 'true') {
                        active.push(btn.dataset.prefix.toUpperCase());
                    }
                });
                return active;
            }

            function matchesChampionnatFilter(row) {
                const activePrefixes = getActivePrefixes();
                if (activePrefixes.length === prefixBtns.length) return true; // all active = no filter
                const championnats = row.dataset.championnats.split('|');
                return championnats.some(nom => {
                    const nomUpper = nom.toUpperCase();
                    return activePrefixes.some(prefix => nomUpper.startsWith(prefix));
                });
            }

            function applyFilters() {
                const search = input.value.toLowerCase().trim();
                let visibleIndex = 0;

                rows.forEach(row => {
                    const cavalier = row.dataset.cavalier || '';
                    const matchesCavalier = !search || cavalier.includes(search);
                    const matchesChamp = matchesChampionnatFilter(row);

                    if (matchesCavalier && matchesChamp) {
                        row.style.display = '';
                        visibleIndex++;
                        row.querySelector('.row-numero').textContent = visibleIndex;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            input.addEventListener('input', applyFilters);

            prefixBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const isActive = this.dataset.active === 'true';
                    this.dataset.active = isActive ? 'false' : 'true';

                    if (this.dataset.active === 'true') {
                        this.className = 'filtre-prefix-btn inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-indigo-100 text-indigo-700 border-indigo-300 hover:bg-indigo-200';
                    } else {
                        this.className = 'filtre-prefix-btn inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-gray-100 text-gray-400 border-gray-200 hover:bg-gray-200';
                    }

                    applyFilters();
                });
            });
        });
    </script>
    @endif
</x-app-layout>
