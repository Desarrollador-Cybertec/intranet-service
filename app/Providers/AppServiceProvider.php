<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El contrato nunca usa el wrapper `data`: recurso único directo, listas en { items }.
        JsonResource::withoutWrapping();

        // El superadministrador nunca se bloquea, ni en middleware ni en policies.
        Gate::before(fn (User $user) => $user->isSuperadmin() ? true : null);

        Gate::define('perm', fn (User $user, string $view, string $action = 'ver') => $user->hasPermission($view, $action));
    }
}
