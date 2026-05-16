@push('head')
    <meta http-equiv="refresh" content="300">
@endpush

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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ventes</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Modifications</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    @can('compta')
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Facture faite</th>
                                    @endcan
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                {{-- Facture Caisse --}}
                                @if ($caisseVentesCount > 0 || $caisseModificationsCount > 0)
                                    @can('compta')
                                        @php
                                            $caisseFaite = $concours->caisse_facture_faite;
                                            $caisseFaiteInfo = '';
                                            if ($caisseFaite && $concours->caisse_facture_faite_par_id) {
                                                $caisseFaiteInfo = 'par ' . optional($concours->caisseFactureFaitePar)->name . ' le ' . $concours->caisse_facture_faite_le?->format('d/m/Y H:i');
                                            }
                                        @endphp
                                    @endcan
                                    <tr @can('compta') x-data="{
                                        faite: {{ ($concours->caisse_facture_faite ?? false) ? 'true' : 'false' }},
                                        faiteInfo: '{{ $caisseFaiteInfo ?? '' }}',
                                        async toggle() {
                                            const r = await fetch(`{{ route('concours.factures.caisse.toggle-faite', $concours) }}`, {
                                                method: 'PATCH',
                                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                            });
                                            if (r.ok) { const d = await r.json(); this.faite = d.facture_faite; this.faiteInfo = d.faite_info; }
                                        }
                                    }" :class="faite ? 'bg-green-50' : 'bg-amber-50'" @else class="bg-amber-50" @endcan>
                                        <td class="px-4 py-3 text-sm font-bold">
                                            <a href="{{ route('concours.factures.caisse', $concours) }}"
                                                class="text-amber-700 hover:text-amber-900 hover:underline">
                                                CAISSE
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400">Sans facturation nominative</td>
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
                                        @can('compta')
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" :checked="faite" @change="toggle()"
                                                    class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 cursor-pointer w-4 h-4">
                                                <span x-show="faite && faiteInfo" x-text="faiteInfo" class="block text-xs text-gray-400 mt-1"></span>
                                            </td>
                                        @endcan
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
                                    @can('compta')
                                        @php
                                            $statut = $factureStatuts[$client->id] ?? null;
                                            $faite = $statut?->facture_faite ?? false;
                                            $faiteInfo = '';
                                            if ($faite && $statut?->factureFaitePar) {
                                                $faiteInfo = 'par ' . $statut->factureFaitePar->name . ' le ' . $statut->facture_faite_le?->format('d/m/Y H:i');
                                            }
                                        @endphp
                                        <tr x-data="{
                                            faite: {{ $faite ? 'true' : 'false' }},
                                            faiteInfo: '{{ $faiteInfo }}',
                                            async toggle() {
                                                const r = await fetch(`{{ route('concours.factures.toggle-faite', [$concours, $client]) }}`, {
                                                    method: 'PATCH',
                                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                                });
                                                if (r.ok) { const d = await r.json(); this.faite = d.facture_faite; this.faiteInfo = d.faite_info; }
                                            }
                                        }" :class="faite ? 'bg-green-50' : ''">
                                    @else
                                        <tr>
                                    @endcan
                                        <td class="px-4 py-3 text-sm font-medium">
                                            <a href="{{ route('concours.factures.show', [$concours, $client]) }}"
                                                class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                {{ $client->nom }}
                                            </a>
                                        </td>
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
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                                            @php $clientTotal = ($client->ventes_sum_total_ttc ?? 0) + ($client->modifications_sum_prix ?? 0); @endphp
                                            @if ($clientTotal > 0)
                                                {{ number_format($clientTotal, 2, ',', ' ') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @can('compta')
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" :checked="faite" @change="toggle()"
                                                    class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 cursor-pointer w-4 h-4">
                                                <span x-show="faite && faiteInfo" x-text="faiteInfo" class="block text-xs text-gray-400 mt-1"></span>
                                            </td>
                                        @endcan
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
            {{-- Bilan comptable du concours --}}
            <div class="mt-6 bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Bilan comptable du concours</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

                    {{-- Recettes globales --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Recettes globales</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between items-center">
                                <dt class="text-sm text-gray-600">Ventes</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ number_format($bilan['ventesTotal'], 2, ',', ' ') }} &euro;</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-sm text-gray-600">Modifications (Fact ET)</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ number_format($bilan['modsTotal'], 2, ',', ' ') }} &euro;</dd>
                            </div>
                            <div class="flex justify-between items-center pt-3 mt-1 border-t border-gray-200">
                                <dt class="text-sm font-bold text-gray-900">Total général</dt>
                                <dd class="text-lg font-bold text-indigo-700">{{ number_format($bilan['total'], 2, ',', ' ') }} &euro;</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Détail par moyen de paiement --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Détail par moyen de paiement</h4>
                        <dl class="space-y-2">
                            @foreach(['cb' => 'CB', 'cheque' => 'Chèque', 'especes' => 'Espèces', 'internet' => 'Internet', 'virement' => 'Virement'] as $mode => $label)
                                <div class="flex justify-between items-center {{ $bilan[$mode] > 0 ? '' : 'opacity-40' }}">
                                    <dt class="text-sm text-gray-600">{{ $label }}</dt>
                                    <dd class="text-sm font-medium {{ $bilan[$mode] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ number_format($bilan[$mode], 2, ',', ' ') }} &euro;</dd>
                                </div>
                            @endforeach
                            <div class="flex justify-between items-center pt-3 mt-1 border-t border-gray-200 {{ $bilan['non_regle'] > 0 ? 'text-red-600' : 'opacity-40' }}">
                                <dt class="text-sm font-medium">Non réglé</dt>
                                <dd class="text-sm font-semibold">{{ number_format($bilan['non_regle'], 2, ',', ' ') }} &euro;</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
