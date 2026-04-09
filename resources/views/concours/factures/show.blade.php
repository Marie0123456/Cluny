<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $concours->nom }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Facture : {{ $client->nom }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Retour -->
            <div class="mb-4">
                <a href="{{ route('concours.factures.index', $concours) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">&larr; Retour aux factures</a>
            </div>

            <!-- Infos client -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">{{ $client->nom }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Téléphone :</span>
                        <span class="text-gray-900 ml-1">{{ $client->telephone ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Email :</span>
                        <span class="text-gray-900 ml-1">{{ $client->email ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Adresse :</span>
                        <span class="text-gray-900 ml-1">{{ $client->adresse ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Totaux -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Ventes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Modifications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Général</p>
                    <p class="text-2xl font-bold text-indigo-900">{{ number_format($totalVentes + $totalModifications, 2, ',', ' ') }} &euro;</p>
                </div>
            </div>

            <!-- Paiement global -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6" x-data="{
                open: false,
                cheque: false
            }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-lg font-medium text-gray-900">Paiement global</h3>
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <span x-text="open ? 'Fermer' : 'Modifier le paiement'"></span>
                    </button>
                </div>

                <form x-show="open" x-cloak method="POST"
                    action="{{ route('concours.factures.update-paiement-global', [$concours, $client]) }}"
                    class="mt-4 border-t pt-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Moyen de paiement -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Moyen de paiement</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="paiement_cb" value="0">
                                    <input type="checkbox" name="paiement_cb" value="1"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">CB</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="paiement_especes" value="0">
                                    <input type="checkbox" name="paiement_especes" value="1"
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">Espèces</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="paiement_cheque" value="0">
                                    <input type="checkbox" name="paiement_cheque" value="1"
                                        x-model="cheque"
                                        class="rounded border-gray-300 text-yellow-600 shadow-sm focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-gray-700">Chèque</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="paiement_internet" value="0">
                                    <input type="checkbox" name="paiement_internet" value="1"
                                        class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700">Internet</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="hidden" name="paiement_virement" value="0">
                                    <input type="checkbox" name="paiement_virement" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Virement</span>
                                </label>
                            </div>
                            <!-- Numéro de chèque -->
                            <div x-show="cheque" class="mt-2">
                                <input type="text" name="numero_cheque" placeholder="Numéro de chèque"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                        </div>

                        <!-- Date de paiement -->
                        <div>
                            <label for="jour_paiement_global" class="block text-sm font-medium text-gray-700 mb-2">Date de paiement</label>
                            <input type="date" name="jour_paiement" id="jour_paiement_global"
                                value="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            onclick="return confirm('Appliquer ce paiement à toutes les ventes et modifications de cette facture ?')">
                            Appliquer à toutes les lignes
                        </button>
                        <span class="text-xs text-gray-500">Cette action mettra à jour le paiement de {{ $ventes->count() }} vente(s) et {{ $modifications->count() }} modification(s)</span>
                    </div>
                </form>

                @if (session('success'))
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                @endif
            </div>

            <!-- Ventes -->
            @if ($ventes->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qte</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. TTC</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($ventes as $vente)
                                    @php $ligneCount = $vente->lignes->count(); @endphp
                                    @foreach ($vente->lignes as $index => $ligne)
                                        <tr class="{{ $index === 0 ? 'border-t-2 border-gray-300' : '' }}">
                                            @if ($index === 0)
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900" rowspan="{{ $ligneCount }}">
                                                    {{ $vente->nom_client }}
                                                    @if ($vente->commentaire)
                                                        <div class="text-xs font-normal text-amber-700 bg-amber-50 rounded px-2 py-1 mt-1">{{ $vente->commentaire }}</div>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $ligne->produit->nom }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $ligne->quantite }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($ligne->prix_unitaire_ttc, 2, ',', ' ') }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($ligne->produit->tva, 1) }}%</td>
                                            @php
                                                $totalHt = round($ligne->total_ttc / (1 + $ligne->produit->tva / 100), 2);
                                            @endphp
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($totalHt, 2, ',', ' ') }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right">{{ number_format($ligne->total_ttc, 2, ',', ' ') }} &euro;</td>
                                            @if ($index === 0)
                                                <td class="px-4 py-3 text-sm text-gray-500" rowspan="{{ $ligneCount }}">
                                                    @if ($vente->paiement_cb)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">CB</span>@endif
                                                    @if ($vente->paiement_especes)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Espèces</span>@endif
                                                    @if ($vente->paiement_cheque)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Chèque</span>@endif
                                                    @if ($vente->paiement_internet)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Internet</span>@endif
                                                    @if ($vente->paiement_virement)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Virement</span>@endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-500" rowspan="{{ $ligneCount }}">
                                                    {{ $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '-' }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total ventes</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalVentes, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Modifications -->
            @if ($modifications->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Modifications payantes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Épreuve</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($modifications as $mod)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $mod->engagement->epreuve->numero ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $mod->engagement->cheval->nom ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                            {{ $mod->pf ? number_format($mod->pf, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                            @if ($mod->prix && $mod->pf !== null)
                                                {{ number_format(($mod->prix - $mod->pf) / (1 + config('ehnc.tva_modifications') / 100), 2, ',', ' ') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right">
                                            {{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @php
                                                $paiements = [];
                                                if ($mod->paiement_cb) $paiements[] = 'CB';
                                                if ($mod->paiement_especes) $paiements[] = 'Espèces';
                                                if ($mod->paiement_cheque) $paiements[] = 'Chèque';
                                                if ($mod->paiement_internet) $paiements[] = 'Internet';
                                                if ($mod->paiement_virement) $paiements[] = 'Virement';
                                            @endphp
                                            {{ $paiements ? implode(', ', $paiements) : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total modifications</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($totalModifications, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
