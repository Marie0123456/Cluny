<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $concours->date_debut->format('d/m/Y') }} - {{ $concours->date_fin->format('d/m/Y') }}
                    &middot; {{ $concours->discipline->value }}
                </p>
            </div>
            @can('admin')
                <div class="flex flex-wrap gap-2">
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
                    <p class="text-sm text-gray-500">Épreuves</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->epreuves_count }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Engagés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->engagements_count }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Modifications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->modifications_count }}</p>
                </div>
                @can('admin')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <p class="text-sm text-gray-500">Ventes</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $concours->ventes_count }}</p>
                    </div>
                @endcan
            </div>

            <!-- Mes Épreuves -->
            @if ($epreuves->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mes Épreuves</h3>
                        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">N.</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Engagés</th>
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
                </div>
            @endif

            <!-- Import / Sync -->
            @can('admin')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $concours->type_ffe_sif ? 'Import FFE SIF' : 'Import / Synchronisation FFE Compet' }}
                    </h3>

                    @if ($concours->type_ffe_compet)
                        {{-- === Bloc FFE Compet : credentials + sync automatique === --}}
                        <div x-data="{ showCredentials: {{ ($concours->ffe_numero_concours ? 'false' : 'true') }} }">

                            {{-- Bouton sync principal (si credentials configurés) --}}
                            @if ($concours->ffe_numero_concours && $concours->ffe_login)
                                <div class="flex items-center gap-4 mb-4">
                                    <form method="POST" action="{{ route('concours.import.ffe-sync', $concours) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Lancer la synchronisation depuis FFE Compet ?\n\nLes nouveaux engagements seront ajoutés et les forfaits détectés automatiquement.')"
                                            class="inline-flex items-center px-5 py-2.5 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700">
                                            Synchroniser depuis FFE Compet
                                        </button>
                                    </form>
                                    <span class="text-sm text-gray-500">Concours n° <strong>{{ $concours->ffe_numero_concours }}</strong> — login : <strong>{{ $concours->ffe_login }}</strong></span>
                                    <button @click="showCredentials = !showCredentials" class="text-sm text-indigo-600 hover:underline">
                                        Modifier les identifiants
                                    </button>
                                </div>
                            @else
                                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2 mb-4">
                                    Configurez les identifiants FFE Compet pour activer la synchronisation automatique.
                                </p>
                            @endif

                            {{-- Formulaire credentials --}}
                            <div x-show="showCredentials" x-cloak class="border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Identifiants FFE Compet</h4>
                                <form method="POST" action="{{ route('concours.import.save-credentials', $concours) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Numéro de concours FFE</label>
                                        <input type="text" name="ffe_numero_concours" value="{{ old('ffe_numero_concours', $concours->ffe_numero_concours) }}"
                                            placeholder="ex : 202671012" required
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Login FFE</label>
                                        <input type="text" name="ffe_login" value="{{ old('ffe_login', $concours->ffe_login) }}"
                                            required autocomplete="off"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            Mot de passe FFE
                                            @if ($concours->ffe_password)
                                                <span class="text-gray-400">(laisser vide pour conserver)</span>
                                            @endif
                                        </label>
                                        <input type="password" name="ffe_password"
                                            placeholder="{{ $concours->ffe_password ? '••••••••' : 'Mot de passe' }}"
                                            autocomplete="new-password"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                            Enregistrer les identifiants
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {{-- Import manuel (fallback) --}}
                            <details class="mt-2">
                                <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">Import manuel par fichier Excel</summary>
                                <div class="mt-3 pl-2 border-l-2 border-gray-200">
                                    <p class="text-xs text-gray-500 mb-2">Téléchargez le fichier depuis FFE Compet ("Extraction excel") et importez-le ici.</p>
                                    <form method="POST" action="{{ route('concours.import.store', $concours) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-end gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <input type="file" name="fichier" accept=".csv,.txt,.tsv,.xls,.xlsx" required
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                            @error('fichier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                            Importer
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>

                    @else
                        {{-- === Bloc FFE SIF : import CSV classique === --}}
                        <p class="text-sm text-gray-500 mb-3">
                            Colonnes attendues :
                            <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">Discipline;Epreuve;Numero Depart;Licence;Nom;Prenom;Club;Sire;Cheval</code>
                        </p>
                        <form method="POST" action="{{ route('concours.import.store', $concours) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-end gap-4">
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
                        <div class="mt-3">
                            <a href="{{ route('import.template-sif') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                                Télécharger le template CSV vide
                            </a>
                        </div>
                    @endif

                    @if ($concours->engagements_count > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <form method="POST" action="{{ route('concours.purge', $concours) }}"
                                onsubmit="return confirm('Attention : cela supprimera TOUTES les épreuves, engagements, cavaliers, chevaux et modifications de ce concours.\n\nCette action est irréversible.\n\nConfirmer la suppression ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Supprimer toutes les données importées
                                </button>
                                <span class="ml-3 text-sm text-gray-500">Supprime épreuves, engagements, cavaliers et chevaux de ce concours.</span>
                            </form>
                        </div>
                    @endif
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
