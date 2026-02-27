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

            @include('concours.partials.tabs', ['active' => 'facturation-et'])

            <!-- Totaux -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Modifications payantes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $modifications->count() }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Prix</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalPrix, 2, ',', ' ') }} &euro;</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total PF</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalPf, 2, ',', ' ') }} &euro;</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                @if ($modifications->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        Aucune modification payante pour le moment.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N. Epreuve</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cavalier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de modif</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jour paiement</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($modifications as $mod)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            {{ $mod->engagement->epreuve->numero ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->engagement->cavalier->prenom ?? '' }} {{ $mod->engagement->cavalier->nom ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mod->type->badgeClass() }}">
                                                {{ $mod->type->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $mod->pf ? number_format($mod->pf, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            {{ $mod->prix ? number_format($mod->prix, 2, ',', ' ') . ' €' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @php
                                                $paiements = [];
                                                if ($mod->paiement_cb) $paiements[] = 'CB';
                                                if ($mod->paiement_especes) $paiements[] = 'Espèces';
                                                if ($mod->paiement_cheque) $paiements[] = 'Chèque';
                                            @endphp
                                            {{ $paiements ? implode(', ', $paiements) : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($mod->facture)
                                                <div class="relative group inline-block">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 cursor-help">
                                                        Oui
                                                    </span>
                                                    @if ($mod->clientFacturation)
                                                        <div class="hidden group-hover:block absolute z-50 bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 p-3 text-sm">
                                                            <p class="font-semibold text-gray-900">{{ $mod->clientFacturation->nom }}</p>
                                                            @if ($mod->clientFacturation->telephone)
                                                                <p class="text-gray-600">Tel: {{ $mod->clientFacturation->telephone }}</p>
                                                            @endif
                                                            @if ($mod->clientFacturation->email)
                                                                <p class="text-gray-600">{{ $mod->clientFacturation->email }}</p>
                                                            @endif
                                                            @if ($mod->clientFacturation->adresse)
                                                                <p class="text-gray-600">{{ $mod->clientFacturation->adresse }}</p>
                                                            @endif
                                                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-white border-r border-b border-gray-200"></div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    Non
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-900">Totaux</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPf, 2, ',', ' ') }} &euro;</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($totalPrix, 2, ',', ' ') }} &euro;</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
