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
        </div>
    </div>
</x-app-layout>
