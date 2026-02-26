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

            <!-- Navigation tabs -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                <nav class="flex border-b border-gray-200">
                    <a href="{{ route('concours.show', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Resume
                    </a>
                    <a href="{{ route('concours.epreuves.index', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Epreuves
                    </a>
                    <a href="{{ route('concours.engages.index', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600">
                        Engages
                    </a>
                </nav>
            </div>

            <!-- Search -->
            <div class="mb-4" x-data="{ search: '' }">
                <input type="text" x-model="search" placeholder="Rechercher un cavalier ou un cheval..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                @forelse ($epreuves as $epreuve)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">
                            Epreuve {{ $epreuve->numero }} - {{ $epreuve->nom }}
                            @if ($epreuve->date)
                                <span class="text-sm text-gray-500 font-normal">({{ $epreuve->date->format('d/m/Y') }})</span>
                            @endif
                            <span class="text-sm text-gray-500 font-normal">- {{ $epreuve->engagements->count() }} engages</span>
                        </h3>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dep.</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Club</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Race</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($epreuve->engagements as $engagement)
                                        <tr x-show="search === '' || '{{ strtolower($engagement->cavalier->nom . ' ' . $engagement->cavalier->prenom . ' ' . $engagement->cheval->nom) }}'.includes(search.toLowerCase())">
                                            <td class="px-4 py-2 text-sm text-gray-900">{{ $engagement->numero_depart }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ $engagement->cavalier->prenom }} {{ $engagement->cavalier->nom }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $engagement->cavalier->club }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900">{{ $engagement->cheval->nom }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $engagement->cheval->race }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $engagement->cheval->age ? $engagement->cheval->age . ' ans' : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                        Aucun engage. Importez un fichier CSV depuis la page du concours.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
