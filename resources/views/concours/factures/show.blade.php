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
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6" x-data="{ editClient: false }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ $client->nom }}</h3>
                    <button type="button" @click="editClient = !editClient"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <span x-text="editClient ? 'Fermer' : 'Modifier les infos'"></span>
                    </button>
                </div>

                <!-- Affichage lecture -->
                <div x-show="!editClient" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
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

                <!-- Formulaire édition -->
                <form x-show="editClient" x-cloak method="POST"
                    action="{{ route('concours.factures.update-client', [$concours, $client]) }}"
                    class="border-t pt-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="client_nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" name="nom" id="client_nom" value="{{ old('nom', $client->nom) }}" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="client_telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input type="text" name="telephone" id="client_telephone" value="{{ old('telephone', $client->telephone) }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="client_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" id="client_email" value="{{ old('email', $client->email) }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="client_adresse" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="adresse" id="client_adresse" value="{{ old('adresse', $client->adresse) }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Commentaire facture (spécifique à ce concours) -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6" x-data="{ editComment: {{ $commentaire ? 'false' : 'true' }} }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Commentaire facture</h3>
                    @if ($commentaire)
                    <button type="button" @click="editComment = !editComment"
                        class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <span x-text="editComment ? 'Fermer' : 'Modifier'"></span>
                    </button>
                @endif
                </div>

                @if ($commentaire)
                    <div x-show="!editComment" class="text-sm text-amber-800 bg-amber-50 rounded-md px-3 py-2">
                        {{ $commentaire->commentaire }}
                    </div>
                @endif

                <form x-show="editComment" x-cloak method="POST"
                    action="{{ route('concours.factures.update-commentaire', [$concours, $client]) }}">
                    @csrf
                    @method('PATCH')
                    <textarea name="commentaire" rows="2" placeholder="Ajouter un commentaire pour cette facture..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('commentaire', $commentaire->commentaire ?? '') }}</textarea>
                    <div class="mt-2 flex items-center gap-2">
                        <button type="submit"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Enregistrer
                        </button>
                        @if ($commentaire)
                            <button type="button" @click="editComment = false" class="text-xs text-gray-500 hover:text-gray-700">Annuler</button>
                        @endif
                    </div>
                </form>
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

            @can('admin')
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
            @endcan

            <!-- Ventes -->
            @if ($ventes->isNotEmpty())
                @php
                    $ventesData = [];
                    foreach ($ventes as $vente) {
                        $paiements = array_values(array_filter([
                            $vente->paiement_cb       ? 'CB'       : null,
                            $vente->paiement_especes  ? 'Espèces'  : null,
                            $vente->paiement_cheque   ? 'Chèque'   : null,
                            $vente->paiement_internet ? 'Internet'  : null,
                            $vente->paiement_virement ? 'Virement'  : null,
                        ]));
                        foreach ($vente->lignes as $ligne) {
                            $tvaPct   = (float) $ligne->produit->tva;
                            $puTtc    = (float) $ligne->prix_unitaire_ttc;
                            $puHt     = round($puTtc / (1 + $tvaPct / 100), 2);
                            $totalTtc = (float) $ligne->total_ttc;
                            $totalHt  = round($totalTtc / (1 + $tvaPct / 100), 2);
                            $ventesData[] = [
                                'client'      => $vente->nom_client ?? '',
                                'commentaire' => $vente->commentaire ?? '',
                                'produit'     => $ligne->produit->nom,
                                'quantite'    => (int) $ligne->quantite,
                                'puHt'        => $puHt,
                                'puTtc'       => $puTtc,
                                'tva'         => $tvaPct,
                                'totalHt'     => $totalHt,
                                'totalTtc'    => $totalTtc,
                                'paiements'   => $paiements,
                                'paiement'    => implode(', ', $paiements) ?: '-',
                                'date'        => $vente->jour_paiement ? $vente->jour_paiement->format('d/m/Y') : '-',
                            ];
                        }
                    }
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg mb-6"
                     x-data="{
                         rows: @js($ventesData),
                         filterProduit: '',
                         filterPaiement: '',
                         get filteredRows() {
                             return this.rows.filter(r => {
                                 if (this.filterProduit  && !r.produit.toLowerCase().includes(this.filterProduit.toLowerCase())) return false;
                                 if (this.filterPaiement && !r.paiements.includes(this.filterPaiement)) return false;
                                 return true;
                             });
                         },
                         get totalQuantite() { return this.filteredRows.reduce((s, r) => s + r.quantite, 0); },
                         get totalHt()       { return this.filteredRows.reduce((s, r) => s + r.totalHt,  0); },
                         get totalTtc()      { return this.filteredRows.reduce((s, r) => s + r.totalTtc, 0); },
                         fmt(n) { return n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                         paiementBadge(p) {
                             const map = {
                                 'CB':       'bg-blue-100 text-blue-800',
                                 'Espèces':  'bg-green-100 text-green-800',
                                 'Chèque':   'bg-yellow-100 text-yellow-800',
                                 'Internet': 'bg-purple-100 text-purple-800',
                                 'Virement': 'bg-indigo-100 text-indigo-800',
                             };
                             return 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' + (map[p] ?? 'bg-gray-100 text-gray-600');
                         },
                     }">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Ventes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div>Produit</div>
                                        <input x-model="filterProduit" type="text" placeholder="Filtrer..."
                                            class="mt-1 block w-full text-xs font-normal normal-case border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-0.5 px-2">
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qté</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">P.U. TTC</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div>Paiement</div>
                                        <select x-model="filterPaiement"
                                            class="mt-1 block w-full text-xs font-normal normal-case border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-0.5 px-1">
                                            <option value="">Tous</option>
                                            <option value="CB">CB</option>
                                            <option value="Espèces">Espèces</option>
                                            <option value="Chèque">Chèque</option>
                                            <option value="Internet">Internet</option>
                                            <option value="Virement">Virement</option>
                                        </select>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(r, i) in filteredRows" :key="i">
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <span x-text="r.client"></span>
                                            <div x-show="r.commentaire" class="text-xs font-normal text-amber-700 bg-amber-50 rounded px-2 py-1 mt-1" x-text="r.commentaire"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="r.produit"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-center" x-text="r.quantite"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="fmt(r.puHt) + ' €'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="fmt(r.puTtc) + ' €'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="r.tva.toFixed(1) + '%'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="fmt(r.totalHt) + ' €'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right" x-text="fmt(r.totalTtc) + ' €'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <template x-for="p in r.paiements" :key="p">
                                                <span :class="paiementBadge(p)" x-text="p"></span>
                                            </template>
                                            <span x-show="r.paiements.length === 0" class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="r.date"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredRows.length === 0">
                                    <td colspan="10" class="px-4 py-6 text-sm text-gray-400 text-center italic">Aucune vente pour ces filtres.</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total quantité</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-center" x-text="totalQuantite"></td>
                                    <td colspan="7"></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total Total HT</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-right" x-text="fmt(totalHt) + ' €'"></td>
                                    <td colspan="3"></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="7" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total ventes</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right"><span x-text="fmt(totalTtc)"></span> &euro;</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Modifications -->
            @if ($modifications->isNotEmpty())
                @php
                    $epreuveLabel = fn($ep) => $ep
                        ? (($ep->numero ? $ep->numero . ' - ' : '') . $ep->nom)
                        : '-';
                    $tva = config('ehnc.tva_modifications');

                    $modsData = $modifications->map(function ($mod) use ($epreuveLabel, $tva) {
                        $pf   = $mod->pf !== null ? (float) $mod->pf : null;
                        $puHt = ($mod->prix && $mod->pf !== null)
                            ? round(($mod->prix - $mod->pf) / (1 + $tva / 100), 2)
                            : null;
                        $paiements = array_values(array_filter([
                            $mod->paiement_cb       ? 'CB'       : null,
                            $mod->paiement_especes  ? 'Espèces'  : null,
                            $mod->paiement_cheque   ? 'Chèque'   : null,
                            $mod->paiement_internet ? 'Internet'  : null,
                            $mod->paiement_virement ? 'Virement'  : null,
                        ]));
                        return [
                            'epreuve'   => (string) $epreuveLabel($mod->engagement->epreuve ?? null),
                            'cavalier'  => trim(($mod->engagement->cavalier->prenom ?? '') . ' ' . ($mod->engagement->cavalier->nom ?? '')),
                            'cheval'    => $mod->engagement->cheval->nom ?? '-',
                            'type'      => $mod->type->value,
                            'typeLabel' => $mod->type->label(),
                            'typeBadge' => $mod->type->badgeClass(),
                            'pf'        => $pf,
                            'puHt'      => $puHt,
                            'prix'      => $mod->prix ? (float) $mod->prix : null,
                            'paiements' => $paiements,
                            'paiement'  => implode(', ', $paiements) ?: '-',
                            'date'      => $mod->jour_paiement ? $mod->jour_paiement->format('d/m/Y') : '-',
                        ];
                    })->toArray();

                    $types = $modifications
                        ->map(fn($m) => ['value' => $m->type->value, 'label' => $m->type->label()])
                        ->unique('value')->values()->toArray();
                    $distinctPuHt = $modifications
                        ->map(fn($m) => ($m->prix && $m->pf !== null)
                            ? round(($m->prix - $m->pf) / (1 + $tva / 100), 2)
                            : null)
                        ->filter(fn($v) => $v !== null)
                        ->unique()->sort()->values()->toArray();
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg" x-data="{
                    mods: @js($modsData),
                    filterType: '',
                    filterCavalier: '',
                    filterPuHt: '',
                    filterPaiement: '',
                    get filteredMods() {
                        return this.mods.filter(m => {
                            if (this.filterType     && m.type !== this.filterType) return false;
                            if (this.filterCavalier && !m.cavalier.toLowerCase().includes(this.filterCavalier.toLowerCase())) return false;
                            if (this.filterPuHt !== '' && (m.puHt === null || parseFloat(m.puHt).toFixed(2) !== parseFloat(this.filterPuHt).toFixed(2))) return false;
                            if (this.filterPaiement && !m.paiements.includes(this.filterPaiement)) return false;
                            return true;
                        });
                    },
                    get totalPrix() { return this.filteredMods.reduce((s, m) => s + (m.prix  ?? 0), 0); },
                    get totalPf()   { return this.filteredMods.reduce((s, m) => s + (m.pf    ?? 0), 0); },
                    get totalPuHt() { return this.filteredMods.reduce((s, m) => s + (m.puHt  ?? 0), 0); },
                    fmt(n) { return n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
                }">
                    <div class="px-4 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Modifications payantes</h3>
                    </div>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Épreuve</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div>Cavalier</div>
                                        <input x-model="filterCavalier" type="text" placeholder="Filtrer..."
                                            class="mt-1 block w-full text-xs font-normal normal-case border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-0.5 px-2">
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cheval</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div>Type</div>
                                        <select x-model="filterType"
                                            class="mt-1 block w-full text-xs font-normal normal-case border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-0.5 px-1">
                                            <option value="">Tous</option>
                                            @foreach ($types as $t)
                                                <option value="{{ $t['value'] }}">{{ $t['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">PF</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                        <div class="flex flex-col items-end gap-1">
                                            <span>P.U. HT</span>
                                            <select x-model="filterPuHt" class="text-xs font-normal normal-case border border-gray-300 rounded px-1 py-0.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Tous</option>
                                                @foreach ($distinctPuHt as $val)
                                                    <option value="{{ $val }}">{{ number_format($val, 2, ',', ' ') }} €</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        <div>Paiement</div>
                                        <select x-model="filterPaiement"
                                            class="mt-1 block w-full text-xs font-normal normal-case border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-0.5 px-1">
                                            <option value="">Tous</option>
                                            <option value="CB">CB</option>
                                            <option value="Espèces">Espèces</option>
                                            <option value="Chèque">Chèque</option>
                                            <option value="Internet">Internet</option>
                                            <option value="Virement">Virement</option>
                                        </select>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(m, i) in filteredMods" :key="i">
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium" x-text="m.epreuve"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="m.cavalier"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="m.cheval"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="m.typeBadge" x-text="m.typeLabel"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="m.pf   !== null ? fmt(m.pf)   + ' €' : '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="m.puHt !== null ? fmt(m.puHt) + ' €' : '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right" x-text="m.prix !== null ? fmt(m.prix) + ' €' : '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="m.paiement"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="m.date"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredMods.length === 0">
                                    <td colspan="9" class="px-4 py-6 text-sm text-gray-400 text-center italic">Aucune modification pour ces filtres.</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total PF</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-right" x-text="fmt(totalPf) + ' €'"></td>
                                    <td colspan="4" class="px-4 py-2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-xs text-gray-500 text-right">Sous-total P.U. HT</td>
                                    <td class="px-4 py-2 text-xs font-medium text-gray-700 text-right" x-text="fmt(totalPuHt) + ' €'"></td>
                                    <td colspan="3" class="px-4 py-2"></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Sous-total modifications</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right" x-text="fmt(totalPrix) + ' €'"></td>
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
