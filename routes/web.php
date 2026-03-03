<?php

use App\Http\Controllers\Admin\ProduitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\CavalierSearchController;
use App\Http\Controllers\Api\ChevalSearchController;
use App\Http\Controllers\Api\ClientFacturationController;
use App\Http\Controllers\Api\EpreuveController as ApiEpreuveController;
use App\Http\Controllers\ChampionnatController;
use App\Http\Controllers\ConcoursController;
use App\Http\Controllers\EngageController;
use App\Http\Controllers\EpreuveController;
use App\Http\Controllers\FacturationEtController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ModificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard — Mes Concours
    Route::get('/dashboard', [ConcoursController::class, 'dashboard'])->name('dashboard');

    // Concours — création/modification/suppression (admin seulement)
    // NB : ces routes doivent être déclarées AVANT index/show pour que
    //       concours/create ne soit pas capturé par concours/{concours}
    Route::middleware('role:admin')->group(function () {
        Route::resource('concours', ConcoursController::class)
            ->parameters(['concours' => 'concours'])
            ->except(['index', 'show']);
    });

    // Concours — consultation (tous les utilisateurs)
    Route::resource('concours', ConcoursController::class)
        ->parameters(['concours' => 'concours'])
        ->only(['index', 'show']);

    // Concours sub-pages (accessible à tous les utilisateurs)
    Route::prefix('concours/{concours}')->name('concours.')->middleware('concours.access')->group(function () {
        Route::get('/epreuves', [EpreuveController::class, 'index'])->name('epreuves.index');
        Route::get('/engages', [EngageController::class, 'index'])->name('engages.index');

        // Modifications
        Route::get('/modifications', [ModificationController::class, 'index'])->name('modifications.index');
        Route::post('/modifications/changement-cheval', [ModificationController::class, 'changementCheval'])->name('modifications.changement-cheval');
        Route::post('/modifications/changement-cavalier', [ModificationController::class, 'changementCavalier'])->name('modifications.changement-cavalier');
        Route::post('/modifications/changement-epreuve', [ModificationController::class, 'changementEpreuve'])->name('modifications.changement-epreuve');
        Route::post('/modifications/invitation', [ModificationController::class, 'invitation'])->name('modifications.invitation');
        Route::post('/modifications/non-partant', [ModificationController::class, 'nonPartant'])->name('modifications.non-partant');
    });

    // Concours sub-pages (admin seulement)
    Route::prefix('concours/{concours}')->name('concours.')->middleware(['concours.access', 'role:admin'])->group(function () {
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');
        Route::delete('/purge', [ConcoursController::class, 'purge'])->name('purge');

        // Facturation ET
        Route::get('/facturation-et', [FacturationEtController::class, 'index'])->name('facturation-et.index');

        // Ventes
        Route::get('/ventes', [VenteController::class, 'index'])->name('ventes.index');
        Route::get('/ventes/create', [VenteController::class, 'create'])->name('ventes.create');
        Route::post('/ventes', [VenteController::class, 'store'])->name('ventes.store');

        // Factures
        Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');
        Route::get('/factures/{client}', [FactureController::class, 'show'])->name('factures.show');

        // Statistiques (FFE SIF Open)
        Route::get('/statistiques', [StatistiqueController::class, 'index'])->name('statistiques.index');
        Route::get('/statistiques/export-cavaliers', [StatistiqueController::class, 'exportCavaliers'])->name('statistiques.export-cavaliers');
        Route::get('/statistiques/export-clubs', [StatistiqueController::class, 'exportClubs'])->name('statistiques.export-clubs');
        Route::get('/statistiques/export-multi-epreuves', [StatistiqueController::class, 'exportMultiEpreuves'])->name('statistiques.export-multi-epreuves');
        Route::get('/statistiques/export-multi-epreuves-chevaux', [StatistiqueController::class, 'exportMultiEpreuvesChevaux'])->name('statistiques.export-multi-epreuves-chevaux');

        // Championnats (FFE SIF Open)
        Route::get('/championnats', [ChampionnatController::class, 'index'])->name('championnats.index');
        Route::post('/championnats', [ChampionnatController::class, 'store'])->name('championnats.store');
        Route::get('/championnats/doublons', [ChampionnatController::class, 'doublons'])->name('championnats.doublons');
        Route::post('/championnats/doublons', [ChampionnatController::class, 'storeDoublons'])->name('championnats.doublons.store');
        Route::get('/championnats/{championnat}', [ChampionnatController::class, 'show'])->name('championnats.show');
        Route::post('/championnats/{championnat}/import-resultats', [ChampionnatController::class, 'importResultats'])->name('championnats.import-resultats');
        Route::delete('/championnats/{championnat}/delete-resultats', [ChampionnatController::class, 'deleteResultats'])->name('championnats.delete-resultats');
        Route::get('/championnats/{championnat}/export-resultats', [ChampionnatController::class, 'exportResultats'])->name('championnats.export-resultats');
        Route::get('/championnats/{championnat}/export-ldp', [ChampionnatController::class, 'exportLDP'])->name('championnats.export-ldp');
        Route::post('/championnats/{championnat}/toggle-libre', [ChampionnatController::class, 'toggleLibre'])->name('championnats.toggle-libre');
        Route::delete('/championnats/{championnat}', [ChampionnatController::class, 'destroy'])->name('championnats.destroy');
    });

    // Modification actions
    Route::patch('/modifications/{modification}/fait', [ModificationController::class, 'marquerFait'])->name('modifications.fait');
    Route::delete('/modifications/{modification}', [ModificationController::class, 'destroy'])->name('modifications.destroy');
    Route::patch('/modifications/{modification}/update-paiement', [ModificationController::class, 'updatePaiement'])->name('modifications.update-paiement');

    // Vente actions (admin seulement)
    Route::middleware('role:admin')->group(function () {
        Route::get('/ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
        Route::get('/ventes/{vente}/edit', [VenteController::class, 'edit'])->name('ventes.edit');
        Route::put('/ventes/{vente}', [VenteController::class, 'update'])->name('ventes.update');
        Route::delete('/ventes/{vente}', [VenteController::class, 'destroy'])->name('ventes.destroy');
    });

    // API endpoints
    Route::prefix('api')->group(function () {
        Route::get('/cavaliers/search', [CavalierSearchController::class, 'search'])->name('api.cavaliers.search');
        Route::get('/chevaux/search', [ChevalSearchController::class, 'search'])->name('api.chevaux.search');
        Route::patch('/epreuves/{epreuve}/prix', [ApiEpreuveController::class, 'updatePrix'])->name('api.epreuves.update-prix');
        Route::get('/clients-facturation/search', [ClientFacturationController::class, 'search'])->name('api.clients-facturation.search');
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/assign-concours', [UserController::class, 'assignConcours'])->name('users.assign-concours');
        Route::resource('produits', ProduitController::class)->except(['show', 'destroy']);
        Route::patch('produits/{produit}/toggle', [ProduitController::class, 'toggleActif'])->name('produits.toggle');
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
