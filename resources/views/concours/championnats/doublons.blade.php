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

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900">Couples multi-championnats</h3>
                <p class="mt-1 text-sm text-gray-500">Pour chaque couple, cochez les championnats auxquels il participe. Les championnats d&eacute;coch&eacute;s apparaitront en orange/effac&eacute;.</p>
            </div>

            @if ($doublons->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">Aucun couple ne participe a plusieurs championnats.</p>
                </div>
            @else
                <form action="{{ route('concours.championnats.doublons.store', $concours) }}" method="POST">
                    @csrf
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cavalier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Club</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cheval</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participe au championnat</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($doublons as $index => $doublon)
                                    @php
                                        $coupleKey = $doublon['cavalier_id'] . '-' . $doublon['cheval_id'];
                                        // Determine which championnats are currently selected (not excluded)
                                        // Default: all checked if no exclusions exist
                                        $hasAnyExclusion = false;
                                        foreach ($doublon['championnats'] as $ch) {
                                            if ($existingExclusions->has($coupleKey . '-' . $ch['id'])) {
                                                $hasAnyExclusion = true;
                                                break;
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $doublon['cavalier_prenom'] }} {{ $doublon['cavalier_nom'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $doublon['club'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $doublon['cheval_nom'] }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <input type="hidden" name="couples[]" value="{{ $coupleKey }}">
                                            <div class="flex flex-col gap-1">
                                                @foreach ($doublon['championnats'] as $ch)
                                                    @php
                                                        $exclKey = $coupleKey . '-' . $ch['id'];
                                                        // Checked if: no exclusions saved yet (default all), or not excluded
                                                        $isChecked = !$hasAnyExclusion || !$existingExclusions->has($exclKey);
                                                    @endphp
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox"
                                                            name="selections[{{ $coupleKey }}][]"
                                                            value="{{ $ch['id'] }}"
                                                            {{ $isChecked ? 'checked' : '' }}
                                                            class="h-4 w-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                        <span class="ml-2 text-sm text-gray-700">{{ $ch['nom'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Enregistrer les selections
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
