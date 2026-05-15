<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('admin', fn ($user) => $user->isAdmin());
        Gate::define('vendeur', fn ($user) => $user->role === Role::VENDEUR || $user->isAdmin());
        Gate::define('chronometreur', fn ($user) => $user->role === Role::CHRONOMETREUR || $user->isAdmin());
    }
}
