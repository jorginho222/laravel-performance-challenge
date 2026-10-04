<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Identity\Domain\UserRole;
use Src\Identity\Infrastructure\Persistence\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/products');
        $this->get('/products')->assertRedirect('/login');

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->component('Identity/Login')
            ->where('auth.user', null));
    }

    public function test_users_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);

        $this->post('/login', ['email' => 'buyer@example.com', 'password' => 'password'])
            ->assertRedirect('/products');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'buyer@example.com']);

        $this->from('/login')
            ->post('/login', ['email' => 'buyer@example.com', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registering_creates_a_signed_in_customer(): void
    {
        $this->post('/register', [
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/products');

        $user = User::where('email', 'buyer@example.com')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_signed_in_users_skip_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/products');
    }
}
