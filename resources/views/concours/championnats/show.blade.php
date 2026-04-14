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

    @php
        $usePct = $championnat->discipline->usesPercentage();
        $valLabel = $usePct ? '%' : 'Pts';
        $hasE2 = $championnat->epreuve2 !== null;
    @endphp

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

            {{-- En-tête du championnat --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ $championnat->nom }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::CSO ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::HUNTER ? 'bg-green-100 text-green-800' : '' }}
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::DRESSAGE ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::EQUIFEEL ? 'bg-pink-100 text-pink-800' : '' }}
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::EQUIFUN ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::ENDURANCE ? 'bg-teal-100 text-teal-800' : '' }}
                    ">{{ $championnat->discipline->value }}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                    <span>Épreuve 1 : <span class="font-medium text-gray-700">{{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}</span></span>
                    @if ($hasE2)
                        <span>Épreuve 2 : <span class="font-medium text-gray-700">{{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}</span></span>
                    @else
                        <span class="italic text-gray-400">Pas de seconde &eacute;preuve</span>
                    @endif
                    <span>Participants : <span class="font-medium text-gray-700">{{ $participants->count() }}</span></span>
                </div>
            </div>

            @if ($championnat->discipline->isManualRanking())
                {{-- Classement manuel (Equifeel / Equifun / Endurance) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6"
                     x-data="classementManuel({{ Js::from($classementManuel) }}, {{ Js::from($concours->id) }}, {{ Js::from($championnat->id) }})" x-cloak>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-md font-medium text-gray-900">Classement manuel</h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Saisis la position (1, 2, 3...) pour chaque couple. <strong>0</strong> = non classé (non-partant, éliminé, hors région).
                                Les couples en <span class="inline-block px-1 bg-amber-100 text-amber-800 rounded">orange</span> sont exclus via la gestion des multi-championnats.
                            </p>
                        </div>
                        <span class="text-xs text-gray-500" x-show="saving">Enregistrement...</span>
                    </div>

                    @if ($classementManuel->isEmpty())
                        <p class="text-sm text-gray-500 italic">Aucun engagé sur l'épreuve {{ $championnat->epreuve1->numero }}.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Position</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(entry, idx) in sorted" :key="entry.cavalier_id + '-' + entry.cheval_id">
                                        <tr :class="rowBg(entry)">
                                            <td class="px-4 py-2">
                                                <input type="number" min="0" max="999"
                                                    :value="entry.position"
                                                    @change="save(entry, $event.target.value)"
                                                    class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="(entry.cavalier_prenom + ' ' + entry.cavalier_nom).trim()"></td>
                                            <td class="px-4 py-2 text-sm text-gray-500" x-text="entry.club ?? ''"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="entry.cheval_nom"></td>
                                            <td class="px-4 py-2 text-center text-xs">
                                                <template x-if="entry.is_excluded">
                                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-medium">Exclu (multi)</span>
                                                </template>
                                                <template x-if="!entry.is_excluded && entry.position > 0">
                                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 font-medium" x-text="entry.position + 'e'"></span>
                                                </template>
                                                <template x-if="!entry.is_excluded && entry.position <= 0">
                                                    <span class="text-gray-400">Non classé</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <script>
                    function classementManuel(initial, concoursId, championnatId) {
                        return {
                            entries: initial,
                            concoursId,
                            championnatId,
                            saving: false,
                            get sorted() {
                                return [...this.entries].sort((a, b) => {
                                    const ap = a.position > 0 ? 0 : 1;
                                    const bp = b.position > 0 ? 0 : 1;
                                    if (ap !== bp) return ap - bp;
                                    if (a.position !== b.position) return a.position - b.position;
                                    return (a.cavalier_nom || '').localeCompare(b.cavalier_nom || '');
                                });
                            },
                            rowBg(e) {
                                if (e.is_excluded) return 'bg-amber-50';
                                if (e.position > 0) return '';
                                return 'text-gray-400';
                            },
                            async save(entry, newValue) {
                                const pos = Math.max(0, Math.min(999, parseInt(newValue) || 0));
                                if (pos === entry.position) return;
                                const prev = entry.position;
                                entry.position = pos;
                                this.saving = true;
                                try {
                                    const res = await fetch(`/concours/${this.concoursId}/championnats/${this.championnatId}/update-position`, {
                                        method: 'PATCH',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            cavalier_id: entry.cavalier_id,
                                            cheval_id: entry.cheval_id,
                                            position: pos,
                                        }),
                                    });
                                    if (!res.ok) throw new Error(res.status);
                                } catch (e) {
                                    entry.position = prev;
                                    alert('Erreur lors de l\'enregistrement.');
                                } finally {
                                    this.saving = false;
                                }
                            },
                        };
                    }
                </script>
            @else
            {{-- Import CSV resultats --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h4 class="text-md font-medium text-gray-900 mb-3">Importer les résultats (CSV)</h4>
                <p class="text-sm text-gray-500 mb-1">
                    L'en-tête du CSV doit contenir les colonnes :
                    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">Cl;...;Cheval;...;Cavalier;...;{{ $usePct ? '% (ou Note, Score)' : 'Points (ou Pts, Pen)' }}{{ !$usePct ? ';...;Temps' : '' }}</code>
                </p>
                <p class="text-xs text-gray-400 mb-4">Les colonnes sont détectées automatiquement par leur nom. Séparateur : point-virgule, tabulation ou virgule.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Import Épreuve 1 --}}
                    <div>
                        <form action="{{ route('concours.championnats.import-resultats', [$concours, $championnat]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="epreuve" value="1">
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Épreuve 1 : {{ $championnat->epreuve1->numero }}
                                        @if ($resultatsEpreuve1->isNotEmpty())
                                            <span class="text-green-600">({{ $resultatsEpreuve1->count() }} résultats)</span>
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
                        @if ($resultatsEpreuve1->isNotEmpty())
                            <form action="{{ route('concours.championnats.delete-resultats', [$concours, $championnat]) }}" method="POST" class="mt-2"
                                onsubmit="return confirm('Supprimer les {{ $resultatsEpreuve1->count() }} résultats de l\'épreuve 1 ?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="epreuve" value="1">
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                    Supprimer résultats E1
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Import Épreuve 2 --}}
                    @if ($hasE2)
                        <div>
                            <form action="{{ route('concours.championnats.import-resultats', [$concours, $championnat]) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="epreuve" value="2">
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Épreuve 2 : {{ $championnat->epreuve2->numero }}
                                            @if ($resultatsEpreuve2->isNotEmpty())
                                                <span class="text-green-600">({{ $resultatsEpreuve2->count() }} résultats)</span>
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
                            @if ($resultatsEpreuve2->isNotEmpty())
                                <form action="{{ route('concours.championnats.delete-resultats', [$concours, $championnat]) }}" method="POST" class="mt-2"
                                    onsubmit="return confirm('Supprimer les {{ $resultatsEpreuve2->count() }} résultats de l\'épreuve 2 ?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="epreuve" value="2">
                                    <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                        Supprimer résultats E2
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bouton Exporter LDP (CSO uniquement, quand epreuve 2 existe et epreuve 1 a des résultats) --}}
            @if ($championnat->discipline === \App\Enums\DisciplineChampionnat::CSO && $hasE2 && $resultatsEpreuve1->isNotEmpty())
                <div class="mb-6">
                    <a href="{{ route('concours.championnats.export-ldp', [$concours, $championnat]) }}"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition">
                        Exporter LDP Épreuve 2
                    </a>
                </div>
            @endif

            {{-- Classement Général du Championnat --}}
            @if ($classementGeneral->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 pb-0 flex items-center justify-between">
                        <h4 class="text-md font-medium text-gray-900">Classement Général - {{ $championnat->nom }}</h4>
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
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $hasE2 ? $valLabel . ' E1' : $valLabel }}</th>
                                    @if (!$usePct)
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $hasE2 ? 'Tps E1' : 'Temps' }}</th>
                                    @endif
                                    @if ($championnat->discipline === \App\Enums\DisciplineChampionnat::DRESSAGE)
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Libre</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">Total %</th>
                                    @endif
                                    @if ($hasE2)
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $valLabel }} E2</th>
                                        @if (!$usePct)
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tps E2</th>
                                        @endif
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">Total {{ $valLabel }}</th>
                                        @if (!$usePct)
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider font-bold">Total Tps</th>
                                        @endif
                                    @endif
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
                                    <tr class="{{ $entry['is_excluded'] ? 'bg-orange-50 opacity-50' : '' }}"
                                        @if ($championnat->discipline === \App\Enums\DisciplineChampionnat::DRESSAGE)
                                            x-data="{
                                                libre: {{ ($entry['libre'] ?? false) ? 'true' : 'false' }},
                                                total: {{ $entry['total_points'] }},
                                                base: {{ $entry['points_e1'] }},
                                                saving: false,
                                                toggle() {
                                                    this.saving = true;
                                                    fetch('{{ route('concours.championnats.toggle-libre', [$concours, $championnat]) }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'Accept': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                        },
                                                        body: JSON.stringify({ cavalier_id: {{ $entry['cavalier_id'] }}, cheval_id: {{ $entry['cheval_id'] }} })
                                                    })
                                                    .then(r => r.json())
                                                    .then(data => {
                                                        this.libre = data.libre;
                                                        this.total = this.base + (data.libre ? 1 : 0);
                                                        this.saving = false;
                                                    })
                                                    .catch(() => { this.saving = false; });
                                                }
                                            }"
                                        @endif
                                    >
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
                                        @if (!$usePct)
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                                {{ $entry['temps_e1'] ? number_format($entry['temps_e1'], 2, ',', '') : '-' }}
                                            </td>
                                        @endif
                                        @if ($championnat->discipline === \App\Enums\DisciplineChampionnat::DRESSAGE)
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                                <button @click="toggle()" class="p-1 rounded hover:bg-gray-100" :disabled="saving" title="Basculer Libre">
                                                    <span x-show="libre" class="text-green-600 font-bold">&#10003;</span>
                                                    <span x-show="!libre" class="text-gray-300">&#9744;</span>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900' }}"
                                                x-text="total.toFixed(2).replace('.', ',')">
                                                {{ number_format($entry['total_points'], 2, ',', '') }}
                                            </td>
                                        @endif
                                        @if ($hasE2)
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : ($entry['statut_e2'] !== 'normal' ? 'text-red-500' : 'text-gray-500') }}">
                                                @if ($entry['statut_e2'] === 'elimine') EL
                                                @elseif ($entry['statut_e2'] === 'non_partant') NP
                                                @elseif ($entry['statut_e2'] === 'abandon') AB
                                                @else {{ number_format($entry['points_e2'], 2, ',', '') }}
                                                @endif
                                            </td>
                                            @if (!$usePct)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-500' }}">
                                                    {{ $entry['temps_e2'] ? number_format($entry['temps_e2'], 2, ',', '') : '-' }}
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900' }}">
                                                {{ number_format($entry['total_points'], 2, ',', '') }}
                                            </td>
                                            @if (!$usePct)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $entry['is_excluded'] ? 'text-orange-400' : 'text-gray-900' }}">
                                                    {{ number_format($entry['total_temps'], 2, ',', '') }}
                                                </td>
                                            @endif
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Classements par épreuve --}}
            @if ($resultatsEpreuve1->isNotEmpty() || $resultatsEpreuve2->isNotEmpty())
                <div class="grid grid-cols-1 {{ $hasE2 ? 'lg:grid-cols-2' : '' }} gap-6 mb-6">
                    {{-- Épreuve 1 --}}
                    @if ($resultatsEpreuve1->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 pb-0">
                                <h4 class="text-sm font-medium text-gray-900">Épreuve 1 : {{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cl.</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $valLabel }}</th>
                                            @if (!$usePct)
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Temps</th>
                                            @endif
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
                                                @if (!$usePct)
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">
                                                        {{ $r->temps ? number_format($r->temps, 2, ',', '') : '-' }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Épreuve 2 --}}
                    @if ($hasE2 && $resultatsEpreuve2->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 pb-0">
                                <h4 class="text-sm font-medium text-gray-900">Épreuve 2 : {{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cl.</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $valLabel }}</th>
                                            @if (!$usePct)
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Temps</th>
                                            @endif
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
                                                @if (!$usePct)
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">
                                                        {{ $r->temps ? number_format($r->temps, 2, ',', '') : '-' }}
                                                    </td>
                                                @endif
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
                <h4 class="text-md font-medium text-gray-900">Participants engagés ({{ $participants->count() }})</h4>
            </div>
            @if ($participants->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">
                        @if ($hasE2)
                            Aucun couple cavalier/cheval ne participe aux deux épreuves.
                        @else
                            Aucun couple cavalier/cheval engagé dans cette épreuve.
                        @endif
                    </p>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
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
            @endif {{-- Fin discipline manualRanking --}}
        </div>
    </div>
</x-app-layout>
