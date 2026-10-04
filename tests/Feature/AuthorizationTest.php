<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Identity\Infrastructure\Persistence\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('catalogChanges')]
    public function test_customers_cannot_change_the_catalog(callable $request): void
    {
        Sanctum::actingAs(User::factory()->create());

        $request($this, ProductModel::factory()->create())->assertForbidden();
    }

    #[DataProvider('catalogChanges')]
    public function test_admins_can_change_the_catalog(callable $request, int $status): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $request($this, ProductModel::factory()->create())->assertStatus($status);
    }

    /**
     * @return array<string, array{callable(self, ProductModel): TestResponse, int}>
     */
    public static function catalogChanges(): array
    {
        return [
            'create category' => [fn (self $t) => $t->postJson('/api/categories', ['name' => 'new category']), 201],
            'update category' => [fn (self $t, ProductModel $p) => $t->putJson("/api/categories/{$p->category_id}", ['name' => 'renamed']), 200],
            'delete category' => [fn (self $t) => $t->deleteJson('/api/categories/'.CategoryModel::factory()->create()->id), 204],
            'create product' => [fn (self $t, ProductModel $p) => $t->postJson('/api/products', [
                'name' => 'new product', 'category_id' => $p->category_id, 'price' => 1, 'stock' => 1, 'status' => 'active',
            ]), 201],
            'update product' => [fn (self $t, ProductModel $p) => $t->putJson("/api/products/{$p->id}", ['stock' => 5]), 200],
            'delete product' => [fn (self $t, ProductModel $p) => $t->deleteJson("/api/products/{$p->id}"), 204],
        ];
    }

    public function test_an_invalid_change_by_a_customer_is_forbidden_before_it_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', [])->assertForbidden();
    }

    #[DataProvider('roles')]
    public function test_every_user_can_browse_the_catalog(bool $admin): void
    {
        Sanctum::actingAs($admin ? User::factory()->admin()->create() : User::factory()->create());
        $product = ProductModel::factory()->create();

        $this->getJson('/api/categories')->assertOk();
        $this->getJson("/api/categories/{$product->category_id}")->assertOk();
        $this->getJson('/api/products')->assertOk();
        $this->getJson("/api/products/{$product->id}")->assertOk();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function roles(): array
    {
        return ['customer' => [false], 'admin' => [true]];
    }

    public function test_customers_can_place_orders(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = ProductModel::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', ['id' => (string) Str::uuid(), 'products' => [['product_id' => $product->id, 'quantity' => 1]]])->assertCreated();
    }

    public function test_admins_cannot_place_orders(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $product = ProductModel::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', ['id' => (string) Str::uuid(), 'products' => [['product_id' => $product->id, 'quantity' => 1]]])->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_the_role_cannot_be_chosen_when_registering(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'mallory@example.com', 'role' => 'customer']);
    }
}
