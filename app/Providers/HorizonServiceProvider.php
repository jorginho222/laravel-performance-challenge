<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Src\Identity\Infrastructure\Persistence\User;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Locally the dashboard is open to everyone; elsewhere only admins can see it.
        Gate::define('viewHorizon', fn (?User $user = null) => $user?->isAdmin() ?? false);
    }
}
