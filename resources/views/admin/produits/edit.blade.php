<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier le produit : {{ $produit->nom }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.produits.update', $produit) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nom" value="Nom du produit" />
                        <x-text-input id="nom" name="nom" type="text" class="mt-1 block w-full" :value="old('nom', $produit->nom)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('nom')" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="prix_ttc" value="Prix TTC (€)" />
                            <x-text-input id="prix_ttc" name="prix_ttc" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('prix_ttc', $produit->prix_ttc)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('prix_ttc')" />
                        </div>

                        <div>
                            <x-input-label for="tva" value="TVA (%)" />
                            <x-text-input id="tva" name="tva" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('tva', $produit->tva)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('tva')" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('admin.produits.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
