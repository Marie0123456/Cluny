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

            @include('concours.partials.tabs', ['active' => 'resume'])

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

            <!-- Mes Epreuves -->
            @if ($epreuves->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mes Epreuves</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">N.</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Engages</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($epreuves as $epreuve)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $epreuve->numero }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $epreuve->nom }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $epreuve->date ? $epreuve->date->format('d/m/Y') : '-' }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $epreuve->engagements_count }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm"
                                            x-data="{ editing: false, prix: '{{ $epreuve->prix ?? '' }}', saving: false }">
                                            <div x-show="!editing" class="flex items-center space-x-2">
                                                <span x-text="prix ? prix + ' EUR' : '-'" class="text-gray-500"></span>
                                                @can('admin')
                                                    <button @click="editing = true" class="text-indigo-600 hover:text-indigo-800 text-xs">modifier</button>
                                                @endcan
                                            </div>
                                            @can('admin')
                                                <div x-show="editing" x-cloak class="flex items-center space-x-2">
                                                    <input type="number" step="0.01" x-model="prix"
                                                        class="w-24 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        @keydown.enter="
                                                            saving = true;
                                                            fetch('/api/epreuves/{{ $epreuve->id }}/prix', {
                                                                method: 'PATCH',
                                                                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                                                                body: JSON.stringify({prix: prix})
                                                            }).then(() => { saving = false; editing = false; });
                                                        "
                                                        @keydown.escape="editing = false">
                                                    <button @click="
                                                        saving = true;
                                                        fetch('/api/epreuves/{{ $epreuve->id }}/prix', {
                                                            method: 'PATCH',
                                                            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                                                            body: JSON.stringify({prix: prix})
                                                        }).then(() => { saving = false; editing = false; });
                                                    " class="text-green-600 hover:text-green-800 text-xs" :disabled="saving">OK</button>
                                                    <button @click="editing = false" class="text-gray-400 hover:text-gray-600 text-xs">Annuler</button>
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

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
