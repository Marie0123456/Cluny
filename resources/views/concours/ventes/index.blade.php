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
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @include('concours.partials.tabs', ['active' => 'ventes'])

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                <a href="{{ route('concours.ventes.create', $concours) }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    + Nouvelle vente
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($ventes->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune vente pour le moment.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date paiement</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produits</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($ventes as $vente)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $vente->nom_client }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $vente->jour_paiement->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        @foreach ($vente->lignes as $ligne)
                                            {{ $ligne->produit->nom }} x{{ $ligne->quantite }}@if (!$loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right">{{ number_format($vente->total_ttc, 2, ',', ' ') }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        @if ($vente->paiement_cb)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>@endif
                                        @if ($vente->paiement_especes)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Especes</span>@endif
                                        @if ($vente->paiement_cheque)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Cheque</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($vente->facture)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Oui</span>
                                        @else
                                            <span class="text-gray-400">Non</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right space-x-2">
                                        <a href="{{ route('ventes.show', $vente) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Voir</a>
                                        <form method="POST" action="{{ route('ventes.destroy', $vente) }}" class="inline"
                                            onsubmit="return confirm('Supprimer cette vente ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Total général</td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalGeneral, 2, ',', ' ') }} &euro;</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
