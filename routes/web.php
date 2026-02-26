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

        // Modifications
        Route::get('/modifications', [ModificationController::class, 'index'])->name('modifications.index');
        Route::post('/modifications/changement-cheval', [ModificationController::class, 'changementCheval'])->name('modifications.changement-cheval');

        // Ventes
        Route::get('/ventes', [VenteController::class, 'index'])->name('ventes.index');
        Route::get('/ventes/create', [VenteController::class, 'create'])->name('ventes.create');
        Route::post('/ventes', [VenteController::class, 'store'])->name('ventes.store');
    });

    // Modification actions
    Route::patch('/modifications/{modification}/fait', [ModificationController::class, 'marquerFait'])->name('modifications.fait');
    Route::delete('/modifications/{modification}', [ModificationController::class, 'destroy'])->name('modifications.destroy');

    // Vente actions
    Route::get('/ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
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
