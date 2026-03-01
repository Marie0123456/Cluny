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
            @include('concours.partials.tabs', ['active' => 'championnats'])

            <div class="mb-4">
                <a href="{{ route('concours.championnats.index', $concours) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Retour aux championnats</a>
            </div>

            {{-- En-tete du championnat --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900">{{ $championnat->nom }}</h3>
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                    <span>Epreuve 1 : <span class="font-medium text-gray-700">{{ $championnat->epreuve1->numero }} - {{ $championnat->epreuve1->nom }}</span></span>
                    <span>Epreuve 2 : <span class="font-medium text-gray-700">{{ $championnat->epreuve2->numero }} - {{ $championnat->epreuve2->nom }}</span></span>
                    <span>Participants : <span class="font-medium text-gray-700">{{ $participants->count() }}</span></span>
                </div>
            </div>

            {{-- Liste des participants --}}
            @if ($participants->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">Aucun couple cavalier/cheval ne participe aux deux epreuves.</p>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cavalier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheval</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($participants as $index => $participant)
                                @php
                                    $pKey = $participant->cavalier_id . '-' . $participant->cheval_id;
                                    $isExcluded = $exclusionKeys->has($pKey);
                                @endphp
                                <tr class="{{ $isExcluded ? 'bg-orange-50 opacity-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $isExcluded ? 'text-orange-500' : 'text-gray-900' }}">
                                        {{ $participant->cavalier_prenom }} {{ $participant->cavalier_nom }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">
                                        {{ $participant->club ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isExcluded ? 'text-orange-400' : 'text-gray-500' }}">
                                        {{ $participant->cheval_nom }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
