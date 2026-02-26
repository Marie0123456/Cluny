<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}
                    &middot; {{ $concours->discipline->value }}
                </p>
            </div>
            @can('admin')
                <div class="flex space-x-2">
                    <a href="{{ route('concours.edit', $concours) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-50">
                        Modifier
                    </a>
                    <form method="POST" action="{{ route('concours.destroy', $concours) }}" onsubmit="return confirm('Supprimer ce concours ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase hover:bg-red-700">
                            Supprimer
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Navigation tabs -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                <nav class="flex border-b border-gray-200">
                    <a href="{{ route('concours.show', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 {{ request()->routeIs('concours.show') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Resume
                    </a>
                    <a href="{{ route('concours.epreuves.index', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 {{ request()->routeIs('concours.epreuves.*') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Epreuves ({{ $concours->epreuves_count }})
                    </a>
                    <a href="{{ route('concours.engages.index', $concours) }}"
                        class="px-6 py-3 text-sm font-medium border-b-2 {{ request()->routeIs('concours.engages.*') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Engages ({{ $concours->engagements_count }})
                    </a>
                </nav>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Epreuves</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->epreuves_count }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Engages</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->engagements_count }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Modifications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->modifications_count }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Ventes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->ventes_count }}</p>
                </div>
            </div>

            <!-- Import CSV -->
            @can('admin')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Importer les engages (FFE Compet)</h3>
                    <form method="POST" action="{{ route('concours.import.store', $concours) }}" enctype="multipart/form-data" class="flex items-end space-x-4">
                        @csrf
                        <div class="flex-1">
                            <label for="fichier" class="block text-sm font-medium text-gray-700 mb-1">Fichier CSV/TXT</label>
                            <input type="file" name="fichier" id="fichier" accept=".csv,.txt,.tsv" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('fichier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Importer
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
