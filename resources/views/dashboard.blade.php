<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mes Concours
            </h2>
            @can('admin')
                <a href="{{ route('concours.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    + Nouveau concours
                </a>
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

            @if ($concoursFuturs->isEmpty() && $concoursPasses->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Aucun concours pour le moment.
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
        </div>
    </div>
</x-app-layout>
