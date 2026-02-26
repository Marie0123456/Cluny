<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ConcoursController;
use App\Http\Controllers\EngageController;
use App\Http\Controllers\EpreuveController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
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
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/assign-concours', [UserController::class, 'assignConcours'])->name('users.assign-concours');
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
