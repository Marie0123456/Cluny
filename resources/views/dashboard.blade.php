<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mes Concours
            </h2>
            @can('admin')
                <div class="flex items-center space-x-3">
                    <a href="{{ route('concours.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        + Nouveau concours
                    </a>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 transition">
                            Restaurer
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50">
                            <p class="text-sm text-gray-500 mb-3">
                                Créer un concours depuis un fichier de sauvegarde JSON.
                            </p>
                            <form method="POST" action="{{ route('concours.restore-new') }}" enctype="multipart/form-data"
                                onsubmit="return confirm('Un nouveau concours sera créé à partir de la sauvegarde.\n\nContinuer ?')">
                                @csrf
                                <input type="file" name="backup_file" accept=".json" required
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 mb-3">
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                                    Restaurer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
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

            @if ($concoursFuturs->isEmpty() && $concoursPasses->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    @cannot('admin')
                        <p class="mb-2 text-lg font-medium text-gray-700">Vous n'avez acces a aucun concours.</p>
                        <p>Vous pouvez demander l'acces a un concours ci-dessous, un administrateur validera votre demande.</p>
                    @else
                        Aucun concours pour le moment.
                    @endcannot
                </div>
            @endif

            {{-- Concours a venir --}}
            @if ($concoursFuturs->isNotEmpty())
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Concours a venir</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    @foreach ($concoursFuturs as $c)
                        <a href="{{ route('concours.show', $c) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $c->nom }}</h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($c->discipline->value === 'CSO') bg-blue-100 text-blue-800
                                        @elseif($c->discipline->value === 'Dressage') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $c->discipline->value }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ $c->date_debut->format('d/m/Y') }} - {{ $c->date_fin->format('d/m/Y') }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($c->grand_national)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Grand National</span>
                                    @endif
                                    @if ($c->type_ffe_compet)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">FFE Compet</span>
                                    @endif
                                    @if ($c->type_ffe_sif)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">FFE SIF</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Concours passes --}}
            @if ($concoursPasses->isNotEmpty())
                <h3 class="text-lg font-semibold text-gray-400 mb-4">Concours passes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 opacity-60">
                    @foreach ($concoursPasses as $c)
                        <a href="{{ route('concours.show', $c) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $c->nom }}</h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($c->discipline->value === 'CSO') bg-blue-100 text-blue-800
                                        @elseif($c->discipline->value === 'Dressage') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $c->discipline->value }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ $c->date_debut->format('d/m/Y') }} - {{ $c->date_fin->format('d/m/Y') }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($c->grand_national)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Grand National</span>
                                    @endif
                                    @if ($c->type_ffe_compet)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">FFE Compet</span>
                                    @endif
                                    @if ($c->type_ffe_sif)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">FFE SIF</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
            {{-- Demander acces (non-admin seulement) --}}
            @cannot('admin')
                @if ($availableConcours->isNotEmpty())
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 mt-10">Demander l'acces a un concours</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($availableConcours as $c)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-dashed border-gray-300">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $c->nom }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($c->discipline->value === 'CSO') bg-blue-100 text-blue-800
                                            @elseif($c->discipline->value === 'Dressage') bg-purple-100 text-purple-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $c->discipline->value }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ $c->date_debut->format('d/m/Y') }} - {{ $c->date_fin->format('d/m/Y') }}
                                    </p>

                                    @if (in_array($c->id, $pendingRequestIds))
                                        <span class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Demande en attente...
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('access-requests.store') }}">
                                            @csrf
                                            <input type="hidden" name="concours_id" value="{{ $c->id }}">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                                Demander l'acces
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endcannot
        </div>
    </div>
</x-app-layout>
