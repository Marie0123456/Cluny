<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vente #{{ $vente->id }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $vente->concours->nom }} — {{ $vente->nom_client }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Infos client -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Client</h3>
                        <p class="text-gray-900 font-medium">{{ $vente->nom_client }}</p>
                        <p class="text-sm text-gray-500">Paiement le {{ $vente->jour_paiement->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Paiement</h3>
                        <div class="flex gap-2">
                            @if ($vente->paiement_cb)<span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>@endif
                            @if ($vente->paiement_especes)<span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Especes</span>@endif
                            @if ($vente->paiement_cheque)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Cheque{{ $vente->numero_cheque ? ' #'.$vente->numero_cheque : '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Facturation -->
                @if ($vente->facture && $vente->clientFacturation)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Facturation</h3>
                        <p class="text-gray-900 font-medium">{{ $vente->clientFacturation->nom }}</p>
                        @if ($vente->clientFacturation->telephone)
                            <p class="text-sm text-gray-600">Tel: {{ $vente->clientFacturation->telephone }}</p>
                        @endif
                        @if ($vente->clientFacturation->email)
                            <p class="text-sm text-gray-600">Email: {{ $vente->clientFacturation->email }}</p>
                        @endif
                        @if ($vente->clientFacturation->adresse)
                            <p class="text-sm text-gray-600">{{ $vente->clientFacturation->adresse }}</p>
                        @endif
                    </div>
                @endif

                <!-- Lignes -->
                <table class="min-w-full divide-y divide-gray-200 mb-6">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Quantité</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix unitaire</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($vente->lignes as $ligne)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $ligne->produit->nom }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 text-center">{{ $ligne->quantite }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 text-right">{{ number_format($ligne->prix_unitaire_ttc, 2, ',', ' ') }} &euro;</td>
                                <td class="px-4 py-2 text-sm text-gray-900 font-medium text-right">{{ number_format($ligne->total_ttc, 2, ',', ' ') }} &euro;</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-sm font-bold text-gray-900 text-right">Total TTC</td>
                            <td class="px-4 py-2 text-sm font-bold text-gray-900 text-right">{{ number_format($vente->total_ttc, 2, ',', ' ') }} &euro;</td>
                        </tr>
                    </tfoot>
                </table>

                @if ($vente->commentaire)
                    <div class="mb-6 p-4 bg-yellow-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-1">Commentaire</h3>
                        <p class="text-sm text-gray-700">{{ $vente->commentaire }}</p>
                    </div>
                @endif

                <div class="flex items-center gap-4">
                    <a href="{{ route('concours.ventes.index', $vente->concours) }}"
                        class="text-sm text-indigo-600 hover:text-indigo-900">Retour aux ventes</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
