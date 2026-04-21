<div class="bg-white shadow-sm sm:rounded-lg mb-6">
    <nav class="flex flex-wrap sm:flex-nowrap sm:overflow-x-auto border-b border-gray-200">
        <a href="{{ route('concours.epreuves.index', $concours) }}"
            class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'epreuves' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Épreuves
        </a>
        <a href="{{ route('concours.engages.index', $concours) }}"
            class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'engages' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Engagés
        </a>
        <a href="{{ route('concours.modifications.index', $concours) }}"
            class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'modifications' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Modifs
        </a>
        @can('admin')
            <a href="{{ route('concours.facturation-et.index', $concours) }}"
                class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'facturation-et' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Fact. ET
            </a>
            <a href="{{ route('concours.ventes.index', $concours) }}"
                class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'ventes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Ventes
            </a>
            <a href="{{ route('concours.factures.index', $concours) }}"
                class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'factures' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Factures
            </a>
            @if ($concours->type_ffe_sif && in_array($concours->discipline, [\App\Enums\Discipline::OPEN, \App\Enums\Discipline::DRESSAGE]))
                <a href="{{ route('concours.statistiques.index', $concours) }}"
                    class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'statistiques' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Stats
                </a>
            @endif
            @if ($concours->type_ffe_sif && $concours->discipline === \App\Enums\Discipline::OPEN)
                <a href="{{ route('concours.championnats.index', $concours) }}"
                    class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'championnats' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Champ.
                </a>
            @endif
        @endcan
        @can('vendeur')
            <a href="{{ route('concours.commande-retraits.index', $concours) }}"
                class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'commande-retraits' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Retraits
            </a>
            @if ($concours->type_ffe_sif)
                <a href="{{ route('concours.commande-retrait-repas.index', $concours) }}"
                    class="px-3 sm:px-6 py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 {{ ($active ?? '') === 'commande-retrait-repas' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Retrait Repas
                </a>
            @endif
        @endcan
    </nav>
</div>
