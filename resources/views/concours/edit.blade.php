<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier : {{ $concours->nom }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('concours.update', $concours) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom du concours</label>
                        <input type="text" name="nom" id="nom" value="{{ old('nom', $concours->nom) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="date_debut" class="block text-sm font-medium text-gray-700">Date de debut</label>
                            <input type="date" name="date_debut" id="date_debut" value="{{ old('date_debut', $concours->date_debut->format('Y-m-d')) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('date_debut') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="date_fin" class="block text-sm font-medium text-gray-700">Date de fin</label>
                            <input type="date" name="date_fin" id="date_fin" value="{{ old('date_fin', $concours->date_fin->format('Y-m-d')) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('date_fin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="discipline" class="block text-sm font-medium text-gray-700">Discipline</label>
                        <select name="discipline" id="discipline" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($disciplines as $d)
                                <option value="{{ $d->value }}" {{ old('discipline', $concours->discipline->value) === $d->value ? 'selected' : '' }}>{{ $d->value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4 space-y-2" x-data="{ isCompet: {{ old('type_ffe_compet', $concours->type_ffe_compet) ? 'true' : 'false' }} }">
                        <label class="flex items-center">
                            <input type="checkbox" name="type_ffe_compet" value="1" {{ old('type_ffe_compet', $concours->type_ffe_compet) ? 'checked' : '' }}
                                x-model="isCompet"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">FFE Compet</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="type_ffe_sif" value="1" {{ old('type_ffe_sif', $concours->type_ffe_sif) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">FFE SIF</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="grand_national" value="1" {{ old('grand_national', $concours->grand_national) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Grand National</span>
                        </label>

                        {{-- Credentials FFE Compet --}}
                        <div x-show="isCompet" x-cloak class="mt-3 p-4 bg-indigo-50 border border-indigo-200 rounded-lg space-y-3">
                            <p class="text-xs font-semibold text-indigo-700">Identifiants FFE Compet</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Numéro de concours FFE</label>
                                    <input type="text" name="ffe_numero_concours" value="{{ old('ffe_numero_concours', $concours->ffe_numero_concours) }}"
                                        placeholder="ex : 202671012"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Login FFE</label>
                                    <input type="text" name="ffe_login" value="{{ old('ffe_login', $concours->ffe_login) }}"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Mot de passe FFE
                                        @if ($concours->ffe_password)
                                            <span class="text-gray-400 font-normal">(laisser vide pour conserver)</span>
                                        @endif
                                    </label>
                                    <input type="password" name="ffe_password"
                                        placeholder="{{ $concours->ffe_password ? '••••••••' : '' }}"
                                        autocomplete="new-password"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('concours.show', $concours) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
