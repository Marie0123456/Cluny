@push('head')
    <meta http-equiv="refresh" content="300">
@endpush

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

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Formulaire de creation --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Créer un championnat</h3>

                <form action="{{ route('concours.championnats.store', $concours) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700">Nom du championnat</label>
                            <input type="text" name="nom" id="nom" required value="{{ old('nom') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ex: Championnat Club 2">
                            @error('nom')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="discipline" class="block text-sm font-medium text-gray-700">Discipline</label>
                            <select name="discipline" id="discipline" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach (\App\Enums\DisciplineChampionnat::cases() as $disc)
                                    @if (!$disc->requiresOpenConcours() || $concours->discipline === \App\Enums\Discipline::OPEN)
                                        <option value="{{ $disc->value }}" {{ old('discipline', 'CSO') == $disc->value ? 'selected' : '' }}>
                                            {{ $disc->value }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('discipline')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="epreuve1_id" class="block text-sm font-medium text-gray-700">Épreuve 1</label>
                            <select name="epreuve1_id" id="epreuve1_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Choisir --</option>
                                @foreach ($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}" {{ old('epreuve1_id') == $epreuve->id ? 'selected' : '' }}>
                                        {{ $epreuve->numero }} - {{ $epreuve->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('epreuve1_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="epreuve2-wrapper">
                            <label for="epreuve2_id" class="block text-sm font-medium text-gray-700">Épreuve 2</label>
                            <select name="epreuve2_id" id="epreuve2_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="" id="epreuve2-empty">-- Choisir --</option>
                                @foreach ($epreuves as $epreuve)
                                    <option value="{{ $epreuve->id }}" {{ old('epreuve2_id') == $epreuve->id ? 'selected' : '' }}>
                                        {{ $epreuve->numero }} - {{ $epreuve->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('epreuve2_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Créer le championnat
                        </button>
                    </div>
                </form>
            </div>

            {{-- Bouton doublons --}}
            @if ($championnats->count() >= 2)
                <div class="mb-6">
                    <a href="{{ route('concours.championnats.doublons', $concours) }}"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Couples multi-championnats
                    </a>
                </div>
            @endif

            {{-- Liste des championnats --}}
            @if ($championnats->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">Aucun championnat créé pour ce concours.</p>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discipline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Épreuve 1</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Épreuve 2</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($championnats as $championnat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="{{ route('concours.championnats.show', [$concours, $championnat]) }}"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            {{ $championnat->nom }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::CSO ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::HUNTER ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::DRESSAGE ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::EQUIFEEL ? 'bg-pink-100 text-pink-800' : '' }}
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::EQUIFUN ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $championnat->discipline === \App\Enums\DisciplineChampionnat::ENDURANCE ? 'bg-teal-100 text-teal-800' : '' }}
                                        ">{{ $championnat->discipline->value }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if ($championnat->epreuve2)
                                            {{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}
                                        @else
                                            <span class="italic text-gray-400">Pas de seconde &eacute;preuve</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('concours.championnats.show', [$concours, $championnat]) }}"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">Voir</a>
                                        <form action="{{ route('concours.championnats.destroy', [$concours, $championnat]) }}"
                                            method="POST" class="inline" onsubmit="return confirm('Supprimer ce championnat ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const disciplineSelect = document.getElementById('discipline');
            const epreuve1Select = document.getElementById('epreuve1_id');
            const epreuve2Select = document.getElementById('epreuve2_id');
            const epreuve2Wrapper = document.getElementById('epreuve2-wrapper');

            // Store all epreuves for filtering
            const allEpreuves = @json($epreuves->map(fn ($e) => ['id' => $e->id, 'label' => $e->numero . ' - ' . $e->nom, 'nom' => $e->nom]));

            // Mots-cles de detection par discipline (insensible a la casse et aux accents).
            // Endurance: matche aussi la faute "endurence".
            const DISCIPLINE_KEYWORDS = {
                'Equifeel': ['equifeel'],
                'Equifun': ['equifun'],
                'Endurance': ['endurance', 'endurence'],
            };

            function normalize(s) {
                return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            function filterEpreuves() {
                const disc = disciplineSelect.value;
                const keywords = DISCIPLINE_KEYWORDS[disc] || [disc.toLowerCase()];

                // Filter epreuves whose name contains any discipline keyword
                const filtered = allEpreuves.filter(function (ep) {
                    const n = normalize(ep.nom);
                    return keywords.some(function (kw) { return n.includes(kw); });
                });

                // If no epreuves match, show all (fallback)
                const list = filtered.length > 0 ? filtered : allEpreuves;

                // Rebuild epreuve1 options
                const oldVal1 = epreuve1Select.value;
                epreuve1Select.innerHTML = '<option value="">-- Choisir --</option>';
                list.forEach(function (ep) {
                    const opt = document.createElement('option');
                    opt.value = ep.id;
                    opt.textContent = ep.label;
                    if (String(ep.id) === oldVal1) opt.selected = true;
                    epreuve1Select.appendChild(opt);
                });

                // Rebuild epreuve2 options
                const oldVal2 = epreuve2Select.value;
                epreuve2Select.innerHTML = '<option value="">-- Choisir --</option>';
                list.forEach(function (ep) {
                    const opt = document.createElement('option');
                    opt.value = ep.id;
                    opt.textContent = ep.label;
                    if (String(ep.id) === oldVal2) opt.selected = true;
                    epreuve2Select.appendChild(opt);
                });
            }

            const concoursDiscipline = @json($concours->discipline->value);

            function toggleEpreuve2() {
                const disc = disciplineSelect.value;
                const oneEpreuveDisciplines = ['Equifeel', 'Equifun', 'Endurance'];
                const needsE2 = !oneEpreuveDisciplines.includes(disc)
                    && (disc !== 'Dressage' || concoursDiscipline === 'Dressage');
                if (needsE2) {
                    epreuve2Wrapper.style.display = '';
                    epreuve2Select.setAttribute('required', 'required');
                } else {
                    epreuve2Wrapper.style.display = 'none';
                    epreuve2Select.value = '';
                    epreuve2Select.removeAttribute('required');
                }
            }

            disciplineSelect.addEventListener('change', function () {
                filterEpreuves();
                toggleEpreuve2();
            });

            // Init on page load
            filterEpreuves();
            toggleEpreuve2();
        });
    </script>
</x-app-layout>
