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

            @include('concours.partials.tabs', ['active' => 'engages'])

            <div x-data="{ search: '', epreuveFilter: '' }">
                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input type="text" x-model="search" placeholder="Rechercher un cavalier ou un cheval..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select x-model="epreuveFilter"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les épreuves</option>
                            @foreach ($epreuves as $epreuve)
                                <option value="{{ $epreuve->id }}">{{ $epreuve->numero }} - {{ $epreuve->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @forelse ($epreuvesByDate as $dateKey => $epreuvesJour)
                    @php
                        $dateLabel = $dateKey === 'sans_date' ? 'Date non définie' : \Carbon\Carbon::parse($dateKey)->locale('fr')->translatedFormat('l d/m/Y');
                        $totalEngages = $epreuvesJour->sum(fn($e) => $e->engagements->count());
                        $daySearchStr = strtolower(addslashes(
                            $epreuvesJour->flatMap(fn($ep) => $ep->engagements->map(fn($e) => ($e->cavalier?->nom ?? '') . ' ' . ($e->cavalier?->prenom ?? '') . ' ' . ($e->cheval?->nom ?? '')))->implode('|||')
                        ));
                    @endphp
                    <div class="mb-6" x-data="{ openDay: true }" x-show="search === '' || '{{ $daySearchStr }}'.includes(search.toLowerCase())">
                        <!-- Date header -->
                        <button @click="openDay = !openDay" type="button"
                            class="w-full flex items-center justify-between px-4 py-3 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition mb-2">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-indigo-500 transition-transform" :class="{ 'rotate-90': openDay }"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <h2 class="text-base font-bold text-indigo-900 capitalize">{{ $dateLabel }}</h2>
                            </div>
                            <span class="text-sm text-indigo-600 font-medium">{{ $epreuvesJour->count() }} épreuves — {{ $totalEngages }} engagés</span>
                        </button>

                        <div x-show="openDay" x-transition x-cloak class="space-y-3 pl-2">
                            @foreach ($epreuvesJour as $epreuve)
                                @php
                                    $epreuveSearchStr = strtolower(addslashes(
                                        $epreuve->engagements->map(fn($e) => ($e->cavalier?->nom ?? '') . ' ' . ($e->cavalier?->prenom ?? '') . ' ' . ($e->cheval?->nom ?? ''))->implode('|||')
                                    ));
                                @endphp
                                <div x-data="{ open: false }"
                                    x-show="(epreuveFilter === '' || epreuveFilter === '{{ $epreuve->id }}') && (search === '' || '{{ $epreuveSearchStr }}'.includes(search.toLowerCase()))"
                                    x-effect="if (search !== '' && '{{ $epreuveSearchStr }}'.includes(search.toLowerCase())) { open = true } else if (search === '') { open = false }">
                                    <div class="bg-white shadow-sm sm:rounded-lg">
                                        <!-- Épreuve accordion header -->
                                        <button @click="open = !open" type="button"
                                            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition rounded-lg">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                                <h3 class="text-base font-semibold text-gray-900">
                                                    Épreuve {{ $epreuve->numero }} — {{ $epreuve->nom }}
                                                </h3>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $epreuve->engagements->count() }} engages
                                            </span>
                                        </button>

                                        <!-- Épreuve accordion body -->
                                        <div x-show="open" x-transition x-cloak>
                                            <div class="border-t border-gray-200 overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dep.</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach ($epreuve->engagements as $engagement)
                                                            <tr x-show="search === '' || '{{ strtolower(addslashes(($engagement->cavalier?->nom ?? '') . ' ' . ($engagement->cavalier?->prenom ?? '') . ' ' . ($engagement->cheval?->nom ?? ''))) }}'.includes(search.toLowerCase())">
                                                                <td class="px-4 py-2 text-sm text-gray-900">
                                                                    @if ($engagement->is_non_partant)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">NP</span>
                                                                    @else
                                                                        {{ $engagement->numero_depart }}
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ $engagement->cavalier?->prenom }} {{ $engagement->cavalier?->nom }}</td>
                                                                <td class="px-4 py-2 text-sm text-gray-500">{{ $engagement->cavalier?->club }}</td>
                                                                <td class="px-4 py-2 text-sm text-gray-900">
                                                                    {{ $engagement->cheval?->nom }}
                                                                    @if (($engagement->modifications_count ?? $engagement->modifications->count()) > 0)
                                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 ml-1">modifié</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                        Aucun engagé. Importez un fichier CSV depuis la page du concours.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
