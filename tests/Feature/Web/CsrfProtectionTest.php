<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Identity\Infrastructure\Persistence\User;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The middleware skips verification while running tests; turn it back on.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_web_requests_without_a_token_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/products')
            ->post('/logout')
            ->assertRedirect('/products')
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertAuthenticatedAs($user);
    }

    public function test_web_requests_with_the_session_token_are_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_same_origin_browser_requests_are_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeader('Sec-Fetch-Site', 'same-origin')
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_the_token_based_api_does_not_need_a_csrf_token(): void
    {
        User::factory()->create(['email' => 'buyer@example.com']);

        $this->postJson('/api/login', ['email' => 'buyer@example.com', 'password' => 'password'])
            ->assertOk();
    }
}
