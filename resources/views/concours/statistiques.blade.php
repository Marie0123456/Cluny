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
            @include('concours.partials.tabs', ['active' => 'statistiques'])

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cavaliers uniques</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats->nb_cavaliers_uniques }}</p>
                        </div>
                        <a href="{{ route('concours.statistiques.export-cavaliers', $concours) }}"
                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Exporter CSV
                        </a>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Clubs differents</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats->nb_clubs }}</p>
                        </div>
                        <a href="{{ route('concours.statistiques.export-clubs', $concours) }}"
                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Exporter CSV
                        </a>
                    </div>
                </div>
            </div>

            {{-- Cavaliers multi-epreuves --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cavaliers faisant plusieurs epreuves</h3>

                <form method="GET" action="{{ route('concours.statistiques.index', $concours) }}" class="mb-4">
                    @foreach ($selectedDisciplinesCombinaisons as $disc)
                        <input type="hidden" name="disciplines_combinaisons[]" value="{{ $disc }}">
                    @endforeach
                    @foreach ($selectedDisciplinesChevaux as $disc)
                        <input type="hidden" name="disciplines_chevaux[]" value="{{ $disc }}">
                    @endforeach

                    <div class="flex items-end space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disciplines</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($disciplines as $disc)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="disciplines[]" value="{{ $disc }}"
                                            {{ in_array($disc, $selectedDisciplines) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $disc }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Afficher
                        </button>
                        @if (! empty($selectedDisciplines) && $multiEpreuveCavaliers->isNotEmpty())
                            <a href="{{ route('concours.statistiques.export-multi-epreuves', array_merge([$concours], ['disciplines' => $selectedDisciplines])) }}"
                                class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Exporter CSV
                            </a>
                        @endif
                    </div>
                </form>

                @if (! empty($selectedDisciplines))
                    @if ($multiEpreuveCavaliers->isEmpty())
                        <p class="text-sm text-gray-500">Aucun cavalier ne fait plusieurs epreuves en {{ implode(' / ', $selectedDisciplines) }}.</p>
                    @else
                        <p class="text-sm text-gray-500 mb-3">{{ $multiEpreuveCavaliers->count() }} cavalier(s) faisant plusieurs epreuves en {{ implode(' / ', $selectedDisciplines) }}</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="multi-epreuves-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none" data-sort="cavalier" data-type="text">
                                            <span class="inline-flex items-center gap-1">Cavalier <span class="sort-arrow text-gray-400">&#x2195;</span></span>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none" data-sort="club" data-type="text">
                                            <span class="inline-flex items-center gap-1">Club <span class="sort-arrow text-gray-400">&#x2195;</span></span>
                                        </th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none" data-sort="nb" data-type="number">
                                            <span class="inline-flex items-center gap-1">Nb epreuves <span class="sort-arrow text-gray-400">&#x2195;</span></span>
                                        </th>
                                        @foreach ($multiEpreuveNoms as $epNom)
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $epNom }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($multiEpreuveCavaliers as $cav)
                                        <tr data-cavalier="{{ $cav->nom }} {{ $cav->prenom }}" data-club="{{ $cav->club ?? '' }}" data-nb="{{ $cav->nb_epreuves }}">
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $cav->nom }} {{ $cav->prenom }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cav->club ?? '-' }}</td>
                                            <td class="px-4 py-2 text-sm text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $cav->nb_epreuves }}
                                                </span>
                                            </td>
                                            @foreach ($multiEpreuveNoms as $epNom)
                                                @php
                                                    $match = collect($cav->epreuves)->firstWhere('nom', $epNom);
                                                @endphp
                                                <td class="px-4 py-2 text-sm text-center {{ $match ? 'text-gray-900 font-medium' : 'text-gray-300' }}">
                                                    {{ $match ? 'N°' . ($match['numero_depart'] ?? '-') : '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const table = document.getElementById('multi-epreuves-table');
                                if (!table) return;
                                const headers = table.querySelectorAll('th[data-sort]');
                                let currentSort = null;
                                let currentDir = 'asc';

                                headers.forEach(function (th) {
                                    th.addEventListener('click', function () {
                                        const key = th.dataset.sort;
                                        const type = th.dataset.type;

                                        if (currentSort === key) {
                                            currentDir = currentDir === 'asc' ? 'desc' : 'asc';
                                        } else {
                                            currentSort = key;
                                            currentDir = type === 'number' ? 'desc' : 'asc';
                                        }

                                        // Update arrows
                                        headers.forEach(function (h) {
                                            h.querySelector('.sort-arrow').innerHTML = '&#x2195;';
                                            h.querySelector('.sort-arrow').className = 'sort-arrow text-gray-400';
                                        });
                                        th.querySelector('.sort-arrow').innerHTML = currentDir === 'asc' ? '&#x2191;' : '&#x2193;';
                                        th.querySelector('.sort-arrow').className = 'sort-arrow text-indigo-600';

                                        // Sort rows
                                        const tbody = table.querySelector('tbody');
                                        const rows = Array.from(tbody.querySelectorAll('tr'));

                                        rows.sort(function (a, b) {
                                            let valA = a.dataset[key] || '';
                                            let valB = b.dataset[key] || '';

                                            if (type === 'number') {
                                                valA = parseInt(valA) || 0;
                                                valB = parseInt(valB) || 0;
                                                return currentDir === 'asc' ? valA - valB : valB - valA;
                                            }

                                            valA = valA.toLowerCase();
                                            valB = valB.toLowerCase();
                                            if (valA < valB) return currentDir === 'asc' ? -1 : 1;
                                            if (valA > valB) return currentDir === 'asc' ? 1 : -1;
                                            return 0;
                                        });

                                        rows.forEach(function (row) { tbody.appendChild(row); });
                                    });
                                });
                            });
                        </script>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Cochez une ou plusieurs disciplines puis cliquez sur Afficher.</p>
                @endif
            </div>
        {{-- Combinaisons d'epreuves --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Combinaisons d'epreuves les plus frequentes</h3>
                <p class="text-sm text-gray-500 mb-4">Pour savoir quelles epreuves ne doivent pas se derouler en meme temps.</p>

                <form method="GET" action="{{ route('concours.statistiques.index', $concours) }}" class="mb-4">
                    {{-- Preserve other selections --}}
                    @foreach ($selectedDisciplines as $disc)
                        <input type="hidden" name="disciplines[]" value="{{ $disc }}">
                    @endforeach
                    @foreach ($selectedDisciplinesChevaux as $disc)
                        <input type="hidden" name="disciplines_chevaux[]" value="{{ $disc }}">
                    @endforeach

                    <div class="flex items-end space-x-4 flex-wrap gap-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disciplines</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($disciplines as $disc)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="disciplines_combinaisons[]" value="{{ $disc }}"
                                            {{ in_array($disc, $selectedDisciplinesCombinaisons) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $disc }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Grouper par</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="combinaisons_group_by" value="cavalier"
                                        {{ $combinaisonsGroupBy === 'cavalier' ? 'checked' : '' }}
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Cavalier</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="combinaisons_group_by" value="cheval"
                                        {{ $combinaisonsGroupBy === 'cheval' ? 'checked' : '' }}
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Cheval</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Afficher
                        </button>
                    </div>
                </form>

                @if (! empty($selectedDisciplinesCombinaisons))
                    @if ($combinaisons->isEmpty())
                        <p class="text-sm text-gray-500">Aucune combinaison trouvee en {{ implode(' / ', $selectedDisciplinesCombinaisons) }}.</p>
                    @else
                        <p class="text-sm text-gray-500 mb-3">{{ $combinaisons->count() }} combinaison(s) trouvee(s) en {{ implode(' / ', $selectedDisciplinesCombinaisons) }} (par {{ $combinaisonsGroupBy }})</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Combinaison d'epreuves</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Nb {{ $combinaisonsGroupBy === 'cheval' ? 'chevaux' : 'cavaliers' }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $combinaisonsGroupBy === 'cheval' ? 'Chevaux' : 'Cavaliers' }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($combinaisons as $combi)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($combi->epreuves as $ep)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">{{ $ep }}</span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $combi->count >= 5 ? 'bg-red-100 text-red-800' : ($combi->count >= 3 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ $combi->count }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">
                                                {{ implode(', ', $combi->entities) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Cochez une ou plusieurs disciplines puis cliquez sur Afficher.</p>
                @endif
            </div>

        {{-- Chevaux multi-epreuves --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Chevaux faisant plusieurs epreuves</h3>

                <form method="GET" action="{{ route('concours.statistiques.index', $concours) }}" class="mb-4">
                    {{-- Preserve other selections --}}
                    @foreach ($selectedDisciplines as $disc)
                        <input type="hidden" name="disciplines[]" value="{{ $disc }}">
                    @endforeach
                    @foreach ($selectedDisciplinesCombinaisons as $disc)
                        <input type="hidden" name="disciplines_combinaisons[]" value="{{ $disc }}">
                    @endforeach

                    <div class="flex items-end space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disciplines</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($disciplines as $disc)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="disciplines_chevaux[]" value="{{ $disc }}"
                                            {{ in_array($disc, $selectedDisciplinesChevaux) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $disc }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Afficher
                        </button>
                        @if (! empty($selectedDisciplinesChevaux) && $multiEpreuveChevaux->isNotEmpty())
                            <a href="{{ route('concours.statistiques.export-multi-epreuves-chevaux', array_merge([$concours], ['disciplines_chevaux' => $selectedDisciplinesChevaux])) }}"
                                class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Exporter CSV
                            </a>
                        @endif
                    </div>
                </form>

                @if (! empty($selectedDisciplinesChevaux))
                    @if ($multiEpreuveChevaux->isEmpty())
                        <p class="text-sm text-gray-500">Aucun cheval ne fait plusieurs epreuves en {{ implode(' / ', $selectedDisciplinesChevaux) }}.</p>
                    @else
                        <p class="text-sm text-gray-500 mb-3">{{ $multiEpreuveChevaux->count() }} cheval(aux) faisant plusieurs epreuves en {{ implode(' / ', $selectedDisciplinesChevaux) }}</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="multi-epreuves-chevaux-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none" data-sort="cheval" data-type="text">
                                            <span class="inline-flex items-center gap-1">Cheval <span class="sort-arrow text-gray-400">&#x2195;</span></span>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SIRE</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700 select-none" data-sort="nb" data-type="number">
                                            <span class="inline-flex items-center gap-1">Nb epreuves <span class="sort-arrow text-gray-400">&#x2195;</span></span>
                                        </th>
                                        @foreach ($multiEpreuveNomsChevaux as $epNom)
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $epNom }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($multiEpreuveChevaux as $ch)
                                        <tr data-cheval="{{ $ch->cheval_nom }}" data-nb="{{ $ch->nb_epreuves }}">
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $ch->cheval_nom }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $ch->num_sire ?? '-' }}</td>
                                            <td class="px-4 py-2 text-sm text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $ch->nb_epreuves }}
                                                </span>
                                            </td>
                                            @foreach ($multiEpreuveNomsChevaux as $epNom)
                                                @php
                                                    $match = collect($ch->epreuves)->firstWhere('nom', $epNom);
                                                @endphp
                                                <td class="px-4 py-2 text-sm text-center {{ $match ? 'text-gray-900 font-medium' : 'text-gray-300' }}">
                                                    {{ $match ? 'N°' . ($match['numero_depart'] ?? '-') : '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const table = document.getElementById('multi-epreuves-chevaux-table');
                                if (!table) return;
                                const headers = table.querySelectorAll('th[data-sort]');
                                let currentSort = null;
                                let currentDir = 'asc';

                                headers.forEach(function (th) {
                                    th.addEventListener('click', function () {
                                        const key = th.dataset.sort;
                                        const type = th.dataset.type;

                                        if (currentSort === key) {
                                            currentDir = currentDir === 'asc' ? 'desc' : 'asc';
                                        } else {
                                            currentSort = key;
                                            currentDir = type === 'number' ? 'desc' : 'asc';
                                        }

                                        headers.forEach(function (h) {
                                            h.querySelector('.sort-arrow').innerHTML = '&#x2195;';
                                            h.querySelector('.sort-arrow').className = 'sort-arrow text-gray-400';
                                        });
                                        th.querySelector('.sort-arrow').innerHTML = currentDir === 'asc' ? '&#x2191;' : '&#x2193;';
                                        th.querySelector('.sort-arrow').className = 'sort-arrow text-indigo-600';

                                        const tbody = table.querySelector('tbody');
                                        const rows = Array.from(tbody.querySelectorAll('tr'));

                                        rows.sort(function (a, b) {
                                            let valA = a.dataset[key] || '';
                                            let valB = b.dataset[key] || '';

                                            if (type === 'number') {
                                                valA = parseInt(valA) || 0;
                                                valB = parseInt(valB) || 0;
                                                return currentDir === 'asc' ? valA - valB : valB - valA;
                                            }

                                            valA = valA.toLowerCase();
                                            valB = valB.toLowerCase();
                                            if (valA < valB) return currentDir === 'asc' ? -1 : 1;
                                            if (valA > valB) return currentDir === 'asc' ? 1 : -1;
                                            return 0;
                                        });

                                        rows.forEach(function (row) { tbody.appendChild(row); });
                                    });
                                });
                            });
                        </script>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Cochez une ou plusieurs disciplines puis cliquez sur Afficher.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
