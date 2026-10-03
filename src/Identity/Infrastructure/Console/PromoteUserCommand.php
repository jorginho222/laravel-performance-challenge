<?php

namespace Src\Identity\Infrastructure\Console;

use Illuminate\Console\Command;
use Src\Identity\Domain\UserRole;
use Src\Identity\Infrastructure\Persistence\User;

/**
 * Makes a user an admin. The role cannot be chosen when registering, so this is how the first
 * admin of a migrated (not reseeded) database is created.
 */
class PromoteUserCommand extends Command
{
    protected $signature = 'user:promote {email : The email of the user to make an admin}';

    protected $description = 'Make a user an admin, so they can manage the catalog';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error("No user with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->components->info("{$user->email} is already an admin.");

            return self::SUCCESS;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->components->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
