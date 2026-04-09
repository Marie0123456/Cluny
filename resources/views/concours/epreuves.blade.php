@push('head')
    <meta http-equiv="refresh" content="300">
@endpush

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
                    <a href="{{ route('concours.backup', $concours) }}" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase hover:bg-green-700">
                        Sauvegarder
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

            @include('concours.partials.tabs', ['active' => 'epreuves'])

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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Ventes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $concours->ventes_count }}</p>
                </div>
            </div>

            <!-- Epreuves table -->
            @if ($epreuves->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500 mb-6">
                    Aucune épreuve. Importez un fichier CSV ci-dessous.
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
                    x-data="prixEditor()" x-cloak>
                    <div class="flex justify-between items-center px-6 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Épreuves</h3>
                        @can('admin')
                            <div>
                                <button x-show="!editing" @click="startEditing()" type="button"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                    Modifier les prix
                                </button>
                                <div x-show="editing" class="flex space-x-2">
                                    <button @click="saveAll()" type="button" :disabled="saving"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition disabled:opacity-50">
                                        <span x-show="!saving">Enregistrer</span>
                                        <span x-show="saving">Enregistrement...</span>
                                    </button>
                                    <button @click="cancelEditing()" type="button"
                                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        @endcan
                    </div>

                    <div x-show="saved" x-transition class="mx-6 mt-3 bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded text-sm">
                        Prix enregistrés avec succès.
                    </div>

                    @if ($concours->type_ffe_sif)
                        <div x-show="editing" x-cloak class="mx-6 mt-3 flex items-center gap-3">
                            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Même prix pour toutes :</label>
                            <input type="number" step="0.01" min="0" x-model="prixCommun"
                                class="w-28 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="0.00">
                            <button @click="appliquerPrixCommun()" type="button"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-100 border border-indigo-300 rounded-md text-xs font-semibold text-indigo-700 uppercase hover:bg-indigo-200 transition">
                                Appliquer
                            </button>
                        </div>
                    @endif

                    {{-- Mobile card layout --}}
                    <div class="sm:hidden divide-y divide-gray-200 mt-3">
                        @foreach ($epreuves as $epreuve)
                            <div class="px-4 py-3">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-sm text-gray-900">{{ $epreuve->numero }}</span>
                                        <span class="text-sm text-gray-700">{{ $epreuve->nom }}</span>
                                        @if ($epreuve->badge_label)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $epreuve->badge_couleur }}">
                                                {{ $epreuve->badge_label }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span>{{ $epreuve->date ? $epreuve->date->format('d/m/Y') : '' }}</span>
                                        <span x-show="!editing"
                                            x-text="prix[{{ $epreuve->id }}] ? parseFloat(prix[{{ $epreuve->id }}]).toFixed(2).replace('.', ',') + ' €' : '-'">
                                        </span>
                                        <input x-show="editing" x-cloak type="number" step="0.01" min="0"
                                            x-model="prix[{{ $epreuve->id }}]"
                                            class="w-24 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $epreuve->engagements_count }}
                                        </span>
                                        @if ($epreuve->invitations_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $epreuve->invitations_count > 12 ? 'bg-red-100 text-red-800' : ($epreuve->invitations_count > 9 ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                                +{{ $epreuve->invitations_count }}
                                            </span>
                                        @endif
                                        @if ($epreuve->non_partants_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $epreuve->non_partants_count }} NP
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table layout --}}
                    <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 mt-3">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engagés</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invitations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($epreuves as $epreuve)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $epreuve->numero }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $epreuve->nom }}
                                        @if ($epreuve->badge_label)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $epreuve->badge_couleur }}">
                                                {{ $epreuve->badge_label }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $epreuve->date ? $epreuve->date->format('d/m/Y') : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span x-show="!editing" class="text-gray-500"
                                            x-text="prix[{{ $epreuve->id }}] ? parseFloat(prix[{{ $epreuve->id }}]).toFixed(2).replace('.', ',') + ' EUR' : '-'">
                                        </span>
                                        <input x-show="editing" x-cloak type="number" step="0.01" min="0"
                                            x-model="prix[{{ $epreuve->id }}]"
                                            class="w-28 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $epreuve->engagements_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($epreuve->invitations_count > 12)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $epreuve->invitations_count }}
                                            </span>
                                        @elseif ($epreuve->invitations_count > 9)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ $epreuve->invitations_count }}
                                            </span>
                                        @elseif ($epreuve->invitations_count > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $epreuve->invitations_count }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($epreuve->non_partants_count > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $epreuve->non_partants_count }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">0</span>
                                        @endif
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Importer les engagés ({{ $concours->type_ffe_sif ? 'FFE SIF' : 'FFE Compet' }})</h3>
                    <p class="text-sm text-gray-500 mb-3">
                        Colonnes attendues :
                        @if ($concours->type_ffe_sif)
                            <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">Numero Concours;Numero Epreuve;Numero Depart;Epreuve;Discipline;Licence;Nom;Prenom;Club;Sire;Cheval</code>
                            <br>
                            <span class="text-xs text-gray-400">Le fichier peut être avec ou sans en-tête.</span>
                        @else
                            <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">Epreuve_numero;Epreuve_nom;Epreuve_date;Num_depart;Nom;Prenom;Role_cavalier;Licence;Club;CRE;Departement;Num_dept;Dept_groom;Cheval;Role_cheval;SIRE;Age;Sexe;Robe;Race</code>
                        @endif
                    </p>
                    <form method="POST" action="{{ route('concours.import.store', $concours) }}" enctype="multipart/form-data" class="sm:flex sm:items-end sm:space-x-4 space-y-3 sm:space-y-0">
                        @csrf
                        <div class="flex-1">
                            <label for="fichier" class="block text-sm font-medium text-gray-700 mb-1">Fichier CSV/TXT</label>
                            <input type="file" name="fichier" id="fichier" accept=".csv,.txt,.tsv" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('fichier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Importer
                        </button>
                    </form>
                    <div class="mt-3">
                        @if ($concours->type_ffe_sif)
                            <a href="{{ route('import.template-sif') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                                Télécharger le template CSV vide
                            </a>
                        @else
                            <a href="{{ route('import.template-compet') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                                Télécharger le template CSV vide
                            </a>
                        @endif
                    </div>

                    @if ($concours->engagements_count > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <form method="POST" action="{{ route('concours.purge', $concours) }}"
                                onsubmit="return confirm('Attention : cela supprimera TOUTES les épreuves, engagements et modifications de ce concours.\n\nCette action est irréversible.\n\nConfirmer la suppression ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Supprimer toutes les données importées
                                </button>
                                <span class="block sm:inline sm:ml-3 mt-2 sm:mt-0 text-sm text-gray-500">Supprime épreuves, engagements et modifications de ce concours.</span>
                            </form>
                        </div>
                    @endif
                </div>

            @endcan
        </div>
    </div>

    <script>
        function prixEditor() {
            return {
                editing: false,
                saving: false,
                saved: false,
                prix: {
                    @foreach ($epreuves as $epreuve)
                        {{ $epreuve->id }}: '{{ $epreuve->prix ?? '' }}',
                    @endforeach
                },
                originalPrix: {},
                prixCommun: '',

                appliquerPrixCommun() {
                    if (this.prixCommun === '') return;
                    const ids = Object.keys(this.prix);
                    ids.forEach(id => { this.prix[id] = this.prixCommun; });
                },

                startEditing() {
                    this.originalPrix = { ...this.prix };
                    this.editing = true;
                    this.saved = false;
                },

                cancelEditing() {
                    this.prix = { ...this.originalPrix };
                    this.editing = false;
                },

                async saveAll() {
                    this.saving = true;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    const ids = Object.keys(this.prix);

                    try {
                        await Promise.all(ids.map(id =>
                            fetch(`/api/epreuves/${id}/prix`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ prix: this.prix[id] || null }),
                            })
                        ));
                        this.editing = false;
                        this.saved = true;
                        setTimeout(() => this.saved = false, 3000);
                    } catch (e) {
                        alert('Erreur lors de l\'enregistrement.');
                    } finally {
                        this.saving = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
