<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\UseCases\CreateOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_creates_an_order_with_a_calculated_total_and_its_lines(): void
    {
        $a = Product::factory()->create(['price' => '10.10']);
        $b = Product::factory()->create(['price' => '0.35']);

        $order = app(CreateOrder::class)->handle($this->user, [
            ['product_id' => $a->id, 'quantity' => 3],
            ['product_id' => $b->id, 'quantity' => 2],
        ]);

        $this->assertSame(1, $order->number);
        $this->assertTrue($order->user->is($this->user));
        $this->assertSame('31.00', $order->total);
        $this->assertEqualsCanonicalizing(
            [$a->id => 3, $b->id => 2],
            $order->products->mapWithKeys(fn ($p) => [$p->id => $p->pivot->quantity])->all(),
        );
        $this->assertDatabaseCount('order_product', 2);
    }

    public function test_order_numbers_are_correlative(): void
    {
        $product = Product::factory()->create(['price' => 1]);
        $useCase = app(CreateOrder::class);

        $numbers = collect(range(1, 3))
            ->map(fn () => $useCase->handle($this->user, [['product_id' => $product->id, 'quantity' => 1]])->number);

        $this->assertSame([1, 2, 3], $numbers->all());
    }

    public function test_a_failed_order_does_not_consume_a_number(): void
    {
        $product = Product::factory()->create(['price' => 1]);
        $useCase = app(CreateOrder::class);

        $useCase->handle($this->user, [['product_id' => $product->id, 'quantity' => 1]]);

        try {
            $useCase->handle($this->user, [['product_id' => $product->id, 'quantity' => 1], ['product_id' => fake()->uuid(), 'quantity' => 1]]);
            $this->fail('A missing product should abort the order.');
        } catch (ModelNotFoundException) {
        }

        $this->assertSame(2, $useCase->handle($this->user, [['product_id' => $product->id, 'quantity' => 1]])->number);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_repeated_products_are_merged_into_one_line(): void
    {
        $product = Product::factory()->create(['price' => '2.50']);

        $order = app(CreateOrder::class)->handle($this->user, [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        $this->assertSame('10.00', $order->total);
        $this->assertSame(4, $order->products->first()->pivot->quantity);
    }

    public function test_the_endpoint_creates_an_order(): void
    {
        Sanctum::actingAs($this->user);
        $product = Product::factory()->create(['name' => 'blue widget', 'price' => '19.99']);

        $this->postJson('/api/orders', ['products' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.number', 1)
            ->assertJsonPath('data.total', '39.98')
            ->assertJsonPath('data.products.0.product_id', $product->id)
            ->assertJsonPath('data.products.0.name', 'blue widget')
            ->assertJsonPath('data.products.0.quantity', 2);

        $this->assertTrue(Order::first()->user->is($this->user));
        $this->assertTrue($this->user->orders->contains(Order::first()));
    }

    public function test_the_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
    }

    #[DataProvider('invalidPayloads')]
    public function test_the_endpoint_validates_the_payload(callable $payload, string $errorKey): void
    {
        Sanctum::actingAs($this->user);
        $product = Product::factory()->create();

        $this->postJson('/api/orders', $payload($product))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errorKey);

        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing products' => [fn () => [], 'products'],
            'empty products' => [fn () => ['products' => []], 'products'],
            'unknown product' => [fn () => ['products' => [['product_id' => '01a0edee-18ce-732e-b88d-ce31ddbe92d5', 'quantity' => 1]]], 'products.0.product_id'],
            'not a uuid' => [fn () => ['products' => [['product_id' => 'abc', 'quantity' => 1]]], 'products.0.product_id'],
            'zero quantity' => [fn (Product $p) => ['products' => [['product_id' => $p->id, 'quantity' => 0]]], 'products.0.quantity'],
            'missing quantity' => [fn (Product $p) => ['products' => [['product_id' => $p->id]]], 'products.0.quantity'],
            'repeated product' => [fn (Product $p) => ['products' => [['product_id' => $p->id, 'quantity' => 1], ['product_id' => $p->id, 'quantity' => 1]]], 'products.0.product_id'],
        ];
    }
}
