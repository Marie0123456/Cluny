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

            {{-- En-tete du championnat --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900">{{ $championnat->nom }}</h3>
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                    <span>Epreuve 1 : <span class="font-medium text-gray-700">{{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}</span></span>
                    <span>Epreuve 2 : <span class="font-medium text-gray-700">{{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}</span></span>
                    <span>Participants : <span class="font-medium text-gray-700">{{ $participants->count() }}</span></span>
                </div>
            </div>

            {{-- Import CSV resultats --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Importer les resultats (CSV)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Import Epreuve 1 --}}
                    <form action="{{ route('concours.championnats.import-resultats', [$concours, $championnat]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="epreuve" value="1">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Epreuve 1 : {{ $championnat->epreuve1->numero }}
                                    @if ($resultatsEpreuve1->isNotEmpty())
                                        <span class="text-green-600">({{ $resultatsEpreuve1->count() }} resultats)</span>
                                    @endif
                                </label>
                                <input type="file" name="csv_file" accept=".csv,.txt" required
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Importer
                            </button>
                        </div>
                    </form>

                    {{-- Import Epreuve 2 --}}
                    <form action="{{ route('concours.championnats.import-resultats', [$concours, $championnat]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="epreuve" value="2">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Epreuve 2 : {{ $championnat->epreuve2->numero }}
                                    @if ($resultatsEpreuve2->isNotEmpty())
                                        <span class="text-green-600">({{ $resultatsEpreuve2->count() }} resultats)</span>
                                    @endif
                                </label>
                                <input type="file" name="csv_file" accept=".csv,.txt" required
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Importer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Bouton Exporter LDP (visible quand epreuve 1 a des resultats) --}}
            @if ($resultatsEpreuve1->isNotEmpty())
                <div class="mb-6">
                    <a href="{{ route('concours.championnats.export-ldp', [$concours, $championnat]) }}"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition">
                        Exporter LDP Epreuve 2
                    </a>
                </div>
            @endif

            {{-- Classement General du Championnat --}}
            @if ($classementGeneral->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 pb-0 flex items-center justify-between">
                        <h4 class="text-md font-medium text-gray-900">Classement General - {{ $championnat->nom }}</h4>
                        <a href="{{ route('concours.championnats.export-resultats', [$concours, $championnat]) }}"
                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Exporter CSV
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cl.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheval</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pts E1</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tps E1</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pts E2</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tps E2</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">Total Pts</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">Total Tps</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php $rang = 0; @endphp
                                @foreach ($classementGeneral as $index => $entry)
                                    @php
                                        if (!$entry['is_excluded']) {
                                            $rang++;
                                        }
                                    @endphp
                                    <tr class="{{ $entry['is_excluded'] ? 'bg-orange-50 opacity-50' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900 font-bold' }}">
                                            {{ $entry['is_excluded'] ? '-' : $rang }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium {{ $entry['is_excluded'] ? 'text-orange-500' : 'text-gray-900' }}">
                                            {{ $entry['cavalier_prenom'] }} {{ $entry['cavalier_nom'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                            {{ $entry['cheval_nom'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                            {{ $entry['club'] ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : ($entry['statut_e1'] !== 'normal' ? 'text-red-500' : 'text-gray-500') }}">
                                            @if ($entry['statut_e1'] === 'elimine') EL
                                            @elseif ($entry['statut_e1'] === 'non_partant') NP
                                            @elseif ($entry['statut_e1'] === 'abandon') AB
                                            @else {{ number_format($entry['points_e1'], 2, ',', '') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                            {{ $entry['temps_e1'] ? number_format($entry['temps_e1'], 2, ',', '') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : ($entry['statut_e2'] !== 'normal' ? 'text-red-500' : 'text-gray-500') }}">
                                            @if ($entry['statut_e2'] === 'elimine') EL
                                            @elseif ($entry['statut_e2'] === 'non_partant') NP
                                            @elseif ($entry['statut_e2'] === 'abandon') AB
                                            @else {{ number_format($entry['points_e2'], 2, ',', '') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                            {{ $entry['temps_e2'] ? number_format($entry['temps_e2'], 2, ',', '') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900' }}">
                                            {{ number_format($entry['total_points'], 2, ',', '') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900' }}">
                                            {{ number_format($entry['total_temps'], 2, ',', '') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Classements par epreuve --}}
            @if ($resultatsEpreuve1->isNotEmpty() || $resultatsEpreuve2->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {{-- Epreuve 1 --}}
                    @if ($resultatsEpreuve1->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 pb-0">
                                <h4 class="text-sm font-medium text-gray-900">Epreuve 1 : {{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cl.</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Pts</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Temps</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($resultatsEpreuve1 as $index => $r)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $index + 1 }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $r->cavalier->prenom }} {{ $r->cavalier->nom }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $r->cheval->nom }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center {{ $r->statut !== 'normal' ? 'text-red-500' : 'text-gray-500' }}">
                                                    @if ($r->statut === 'elimine') EL
                                                    @elseif ($r->statut === 'non_partant') NP
                                                    @elseif ($r->statut === 'abandon') AB
                                                    @else {{ number_format($r->points, 2, ',', '') }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">
                                                    {{ $r->temps ? number_format($r->temps, 2, ',', '') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Epreuve 2 --}}
                    @if ($resultatsEpreuve2->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 pb-0">
                                <h4 class="text-sm font-medium text-gray-900">Epreuve 2 : {{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cl.</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Pts</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Temps</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($resultatsEpreuve2 as $index => $r)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $index + 1 }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $r->cavalier->prenom }} {{ $r->cavalier->nom }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $r->cheval->nom }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center {{ $r->statut !== 'normal' ? 'text-red-500' : 'text-gray-500' }}">
                                                    @if ($r->statut === 'elimine') EL
                                                    @elseif ($r->statut === 'non_partant') NP
                                                    @elseif ($r->statut === 'abandon') AB
                                                    @else {{ number_format($r->points, 2, ',', '') }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">
                                                    {{ $r->temps ? number_format($r->temps, 2, ',', '') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Liste des participants (engages) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-2">
                <h4 class="text-md font-medium text-gray-900">Participants engages ({{ $participants->count() }})</h4>
            </div>
            @if ($participants->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">Aucun couple cavalier/cheval ne participe aux deux epreuves.</p>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cavalier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheval</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($participants as $index => $participant)
                                @php
                                    $pKey = $participant->cavalier_id . '-' . $participant->cheval_id;
                                    $isExcluded = $exclusionKeys->has($pKey);
                                @endphp
                                <tr class="{{ $isExcluded ? 'bg-orange-50 opacity-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $isExcluded ? 'text-orange-500' : 'text-gray-900' }}">
                                        {{ $participant->cavalier_prenom }} {{ $participant->cavalier_nom }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">
                                        {{ $participant->club ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">
                                        {{ $participant->cheval_nom }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
