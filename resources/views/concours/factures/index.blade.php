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
            @include('concours.partials.tabs', ['active' => 'factures'])

            @if ($clients->isNotEmpty())
                <div class="flex justify-end mb-4 space-x-3">
                    <a href="{{ route('concours.factures.export-csv', $concours) }}"
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                        Export CSV
                    </a>
                    <a href="{{ route('concours.factures.print', $concours) }}" target="_blank"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Imprimer / PDF
                    </a>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                @if ($clients->isEmpty() && $caisseVentesCount === 0 && $caisseModificationsCount === 0)
                    <div class="p-6 text-center text-gray-500">
                        Aucun client de facturation pour le moment.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telephone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ventes</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Modifications</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                {{-- Facture Caisse --}}
                                @if ($caisseVentesCount > 0 || $caisseModificationsCount > 0)
                                    <tr class="bg-amber-50">
                                        <td class="px-4 py-3 text-sm font-bold">
                                            <a href="{{ route('concours.factures.caisse', $concours) }}"
                                                class="text-amber-700 hover:text-amber-900 hover:underline">
                                                CAISSE
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400" colspan="2">Sans facturation nominative</td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            @if ($caisseVentesCount > 0)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    {{ $caisseVentesCount }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            @if ($caisseModificationsCount > 0)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    {{ $caisseModificationsCount }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-bold text-amber-800">
                                            {{ number_format($caisseTotal, 2, ',', ' ') }} &euro;
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('concours.factures.caisse', $concours) }}"
                                                class="text-amber-500 hover:text-amber-700" title="Voir détail">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Clients facturés --}}
                                @foreach ($clients as $client)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium">
                                            <a href="{{ route('concours.factures.show', [$concours, $client]) }}"
                                                class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                {{ $client->nom }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $client->telephone ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $client->email ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            @if ($client->ventes_count > 0)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $client->ventes_count }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            @if ($client->modifications_count > 0)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $client->modifications_count }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500">-</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('concours.factures.show', [$concours, $client]) }}"
                                                class="text-gray-400 hover:text-indigo-600" title="Voir détail">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
