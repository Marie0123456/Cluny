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

                <form method="GET" action="{{ route('concours.statistiques.index', $concours) }}" class="flex items-end space-x-4 mb-4">
                    <div>
                        <label for="discipline" class="block text-sm font-medium text-gray-700 mb-1">Discipline</label>
                        <select name="discipline" id="discipline"
                            class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Choisir --</option>
                            @foreach ($disciplines as $disc)
                                <option value="{{ $disc }}" {{ $discipline === $disc ? 'selected' : '' }}>{{ $disc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        Afficher
                    </button>
                    @if ($discipline && $multiEpreuveCavaliers->isNotEmpty())
                        <a href="{{ route('concours.statistiques.export-multi-epreuves', [$concours, 'discipline' => $discipline]) }}"
                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                            Exporter CSV
                        </a>
                    @endif
                </form>

                @if ($discipline)
                    @if ($multiEpreuveCavaliers->isEmpty())
                        <p class="text-sm text-gray-500">Aucun cavalier ne fait plusieurs epreuves en {{ $discipline }}.</p>
                    @else
                        <p class="text-sm text-gray-500 mb-3">{{ $multiEpreuveCavaliers->count() }} cavalier(s) faisant plusieurs epreuves en {{ $discipline }}</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Nb epreuves</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Epreuves</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($multiEpreuveCavaliers as $cav)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $cav->nom }} {{ $cav->prenom }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cav->club ?? '-' }}</td>
                                            <td class="px-4 py-2 text-sm text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $cav->nb_epreuves }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cav->epreuves_liste }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Selectionnez une discipline pour voir les cavaliers faisant plusieurs epreuves.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
