<?php

use App\Http\Controllers\Admin\ProduitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\CavalierSearchController;
use App\Http\Controllers\Api\ChevalSearchController;
use App\Http\Controllers\Api\ClientFacturationController;
use App\Http\Controllers\Api\EpreuveController as ApiEpreuveController;
use App\Http\Controllers\ConcoursController;
use App\Http\Controllers\EngageController;
use App\Http\Controllers\EpreuveController;
use App\Http\Controllers\FacturationEtController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ModificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard — Mes Concours
    Route::get('/dashboard', [ConcoursController::class, 'dashboard'])->name('dashboard');

    // Concours CRUD
    Route::resource('concours', ConcoursController::class)->parameters(['concours' => 'concours']);

    // Concours sub-pages
    Route::prefix('concours/{concours}')->name('concours.')->middleware('concours.access')->group(function () {
        Route::get('/engages', [EngageController::class, 'index'])->name('engages.index');
        Route::get('/epreuves', [EpreuveController::class, 'index'])->name('epreuves.index');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');
        Route::delete('/purge', [ConcoursController::class, 'purge'])->name('purge');

        // Modifications
        Route::get('/modifications', [ModificationController::class, 'index'])->name('modifications.index');
        Route::post('/modifications/changement-cheval', [ModificationController::class, 'changementCheval'])->name('modifications.changement-cheval');
        Route::post('/modifications/changement-cavalier', [ModificationController::class, 'changementCavalier'])->name('modifications.changement-cavalier');
        Route::post('/modifications/changement-epreuve', [ModificationController::class, 'changementEpreuve'])->name('modifications.changement-epreuve');
        Route::post('/modifications/invitation', [ModificationController::class, 'invitation'])->name('modifications.invitation');
        Route::post('/modifications/non-partant', [ModificationController::class, 'nonPartant'])->name('modifications.non-partant');

        // Facturation ET
        Route::get('/facturation-et', [FacturationEtController::class, 'index'])->name('facturation-et.index');

        // Ventes
        Route::get('/ventes', [VenteController::class, 'index'])->name('ventes.index');
        Route::get('/ventes/create', [VenteController::class, 'create'])->name('ventes.create');
        Route::post('/ventes', [VenteController::class, 'store'])->name('ventes.store');
    });

    // Modification actions
    Route::patch('/modifications/{modification}/fait', [ModificationController::class, 'marquerFait'])->name('modifications.fait');
    Route::delete('/modifications/{modification}', [ModificationController::class, 'destroy'])->name('modifications.destroy');
    Route::patch('/modifications/{modification}/update-paiement', [ModificationController::class, 'updatePaiement'])->name('modifications.update-paiement');

    // Vente actions
    Route::get('/ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
    Route::get('/ventes/{vente}/edit', [VenteController::class, 'edit'])->name('ventes.edit');
    Route::put('/ventes/{vente}', [VenteController::class, 'update'])->name('ventes.update');
    Route::delete('/ventes/{vente}', [VenteController::class, 'destroy'])->name('ventes.destroy');

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
