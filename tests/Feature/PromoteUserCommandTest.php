<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Identity\Domain\UserRole;
use Src\Identity\Infrastructure\Persistence\User;
use Tests\TestCase;

class PromoteUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_makes_a_customer_an_admin(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $other = User::factory()->create();

        $this->artisan('user:promote', ['email' => 'buyer@example.com'])
            ->expectsOutputToContain('buyer@example.com is now an admin.')
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
        $this->assertSame(UserRole::Customer, $other->fresh()->role);
    }

    public function test_promoting_an_admin_changes_nothing(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->artisan('user:promote', ['email' => 'admin@example.com'])
            ->expectsOutputToContain('already an admin')
            ->assertSuccessful();
    }

    public function test_it_fails_for_an_unknown_email(): void
    {
        $this->artisan('user:promote', ['email' => 'nobody@example.com'])
            ->expectsOutputToContain('No user with email nobody@example.com.')
            ->assertFailed();
    }
}
