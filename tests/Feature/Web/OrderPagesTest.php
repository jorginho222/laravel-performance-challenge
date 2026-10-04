<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Identity\Infrastructure\Persistence\User;
use Src\Ordering\Infrastructure\Persistence\OrderModel;
use Tests\TestCase;

class OrderPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_see_the_cart_and_admins_do_not(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/cart')
            ->assertInertia(fn (Assert $page) => $page->component('Ordering/Cart'));

        $this->actingAs(User::factory()->admin()->create())->get('/cart')->assertForbidden();
    }

    public function test_placing_an_order_redirects_to_the_order_page(): void
    {
        $user = User::factory()->create();
        $product = ProductModel::factory()->create(['name' => 'blue widget', 'price' => '19.99', 'stock' => 10]);

        $response = $this->actingAs($user)
            ->post('/orders', ['products' => [['product_id' => $product->id, 'quantity' => 2]]]);

        $order = OrderModel::sole();
        $response->assertRedirect("/orders/{$order->id}");
        $this->assertSame(8, $product->fresh()->stock);

        $this->get("/orders/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Ordering/OrderShow')
            ->where('order.id', $order->id)
            ->where('order.number', 1)
            ->where('order.total', '39.98')
            ->where('order.user_id', $user->id)
            ->has('order.products', 1)
            ->where('order.products.0.name', 'blue widget')
            ->where('order.products.0.price', '19.99')
            ->where('order.products.0.quantity', 2)
            ->has('order.created_at')
            ->where('auth.user.email', $user->email));
    }

    public function test_customers_cannot_see_other_customers_orders(): void
    {
        $product = ProductModel::factory()->create(['stock' => 10]);
        $this->actingAs(User::factory()->create())
            ->post('/orders', ['products' => [['product_id' => $product->id, 'quantity' => 1]]]);

        $this->actingAs(User::factory()->create())
            ->get('/orders/'.OrderModel::sole()->id)
            ->assertForbidden();
    }

    public function test_an_unknown_order_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/orders/01a0edee-18ce-732e-b88d-ce31ddbe92d5')
            ->assertNotFound();
    }

    public function test_unavailable_products_return_to_the_cart_with_an_error_per_product(): void
    {
        $product = ProductModel::factory()->create(['stock' => 1]);

        $this->actingAs(User::factory()->create())
            ->from('/cart')
            ->post('/orders', ['products' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertRedirect('/cart')
            ->assertSessionHasErrors(["products.{$product->id}" => "Product {$product->name} does not have enough stock."]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admins_cannot_place_orders(): void
    {
        $product = ProductModel::factory()->create(['stock' => 10]);

        $this->actingAs(User::factory()->admin()->create())
            ->post('/orders', ['products' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertForbidden();
    }
}
