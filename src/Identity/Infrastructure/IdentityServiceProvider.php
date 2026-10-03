<?php

namespace Src\Identity\Infrastructure;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Src\Identity\Infrastructure\Console\PromoteUserCommand;
use Src\Identity\Infrastructure\Persistence\User;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Abilities by role. Other contexts check them by name (directly or from their policies),
     * so they never depend on the User model or on what a role means.
     */
    public function boot(): void
    {
        Gate::define('manage-catalog', fn (User $user) => $user->isAdmin());
        Gate::define('place-orders', fn (User $user) => $user->isCustomer());

        // Commands are only auto-discovered in app/Console/Commands.
        if ($this->app->runningInConsole()) {
            $this->commands([PromoteUserCommand::class]);
        }
    }
}
