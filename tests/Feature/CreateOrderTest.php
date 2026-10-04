<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Identity\Infrastructure\Persistence\User;
use Src\Ordering\Application\CreateOrder\CreateOrder;
use Src\Ordering\Application\CreateOrder\CreateOrderDto;
use Src\Ordering\Application\CreateOrder\OrderItem;
use Src\Ordering\Application\OrderData;
use Src\Ordering\Application\OrderLineData;
use Src\Ordering\Domain\Exceptions\ProductNotFound;
use Src\Ordering\Domain\Exceptions\ProductsUnavailable;
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
        $a = ProductModel::factory()->create(['price' => '10.10', 'stock' => 10]);
        $b = ProductModel::factory()->create(['price' => '0.35', 'stock' => 10]);

        $order = $this->createOrder([$a->id => 3, $b->id => 2]);

        $this->assertSame(1, $order->number);
        $this->assertSame($this->user->id, $order->customerId);
        $this->assertSame('31.00', $order->total);
        $this->assertEqualsCanonicalizing([$a->id => 3, $b->id => 2], $this->lineQuantities($order));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $this->user->id, 'number' => 1, 'total' => '31.00']);
        $this->assertDatabaseCount('order_product', 2);
    }

    public function test_order_numbers_are_correlative(): void
    {
        $product = ProductModel::factory()->create(['price' => 1, 'stock' => 10]);

        $numbers = collect(range(1, 3))->map(fn () => $this->createOrder([$product->id => 1])->number);

        $this->assertSame([1, 2, 3], $numbers->all());
    }

    public function test_a_failed_order_does_not_consume_a_number(): void
    {
        $product = ProductModel::factory()->create(['price' => 1, 'stock' => 10]);

        $this->createOrder([$product->id => 1]);

        try {
            $this->createOrder([$product->id => 1, fake()->uuid() => 1]);
            $this->fail('A missing product should abort the order.');
        } catch (ProductNotFound) {
        }

        $this->assertSame(2, $this->createOrder([$product->id => 1])->number);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_repeated_products_are_merged_into_one_line(): void
    {
        $product = ProductModel::factory()->create(['price' => '2.50', 'stock' => 10]);

        $order = $this->handle([
            new OrderItem($product->id, 1),
            new OrderItem($product->id, 3),
        ]);

        $this->assertSame('10.00', $order->total);
        $this->assertSame([$product->id => 4], $this->lineQuantities($order));
    }

    public function test_it_discounts_the_stock_of_the_ordered_products(): void
    {
        $product = ProductModel::factory()->create(['stock' => 10]);

        $this->handle([
            new OrderItem($product->id, 3),
            new OrderItem($product->id, 2),
        ]);

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_it_rejects_inactive_products_and_changes_nothing(): void
    {
        $ok = ProductModel::factory()->create(['stock' => 10]);
        $inactive = ProductModel::factory()->create(['stock' => 10, 'status' => 'inactive']);

        try {
            $this->createOrder([$ok->id => 1, $inactive->id => 1]);
            $this->fail('An inactive product should abort the order.');
        } catch (ProductsUnavailable $e) {
            $this->assertSame([$inactive->id], array_keys($e->reasons));
        }

        $this->assertSame(10, $ok->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_rejects_products_without_enough_stock_and_changes_nothing(): void
    {
        $ok = ProductModel::factory()->create(['stock' => 10]);
        $short = ProductModel::factory()->create(['stock' => 2]);

        try {
            $this->createOrder([$ok->id => 1, $short->id => 3]);
            $this->fail('Insufficient stock should abort the order.');
        } catch (ProductsUnavailable $e) {
            $this->assertSame([$short->id], array_keys($e->reasons));
        }

        $this->assertSame(10, $ok->fresh()->stock);
        $this->assertSame(2, $short->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_endpoint_responds_unprocessable_when_stock_is_insufficient(): void
    {
        Sanctum::actingAs($this->user);
        $product = ProductModel::factory()->create(['stock' => 1]);

        $this->postJson('/api/orders', ['id' => (string) Str::uuid(), 'products' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["products.{$product->id}" => "Product {$product->name} does not have enough stock."]);
    }

    public function test_the_endpoint_creates_an_order(): void
    {
        Sanctum::actingAs($this->user);
        $product = ProductModel::factory()->create(['name' => 'blue widget', 'price' => '19.99', 'stock' => 10]);
        $id = (string) Str::uuid();

        $this->postJson('/api/orders', ['id' => $id, 'products' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertCreated()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.number', 1)
            ->assertJsonPath('data.total', '39.98')
            ->assertJsonPath('data.products.0.product_id', $product->id)
            ->assertJsonPath('data.products.0.name', 'blue widget')
            ->assertJsonPath('data.products.0.price', '19.99')
            ->assertJsonPath('data.products.0.quantity', 2);

        $this->assertDatabaseHas('orders', ['id' => $id, 'user_id' => $this->user->id, 'number' => 1]);
    }

    public function test_the_endpoint_rejects_a_resubmitted_order(): void
    {
        Sanctum::actingAs($this->user);
        $product = ProductModel::factory()->create(['stock' => 10]);
        $payload = ['id' => (string) Str::uuid(), 'products' => [['product_id' => $product->id, 'quantity' => 2]]];

        $this->postJson('/api/orders', $payload)->assertCreated();
        $this->postJson('/api/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id' => 'This order has already been placed.']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_the_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
    }

    #[DataProvider('invalidPayloads')]
    public function test_the_endpoint_validates_the_payload(callable $payload, string $errorKey): void
    {
        Sanctum::actingAs($this->user);
        $product = ProductModel::factory()->create();

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
            'missing id' => [fn (ProductModel $p) => ['products' => [['product_id' => $p->id, 'quantity' => 1]]], 'id'],
            'id not a uuid' => [fn (ProductModel $p) => ['id' => 'abc', 'products' => [['product_id' => $p->id, 'quantity' => 1]]], 'id'],
            'id not a v4 uuid' => [fn (ProductModel $p) => ['id' => (string) Str::uuid7(), 'products' => [['product_id' => $p->id, 'quantity' => 1]]], 'id'],
            'missing products' => [fn () => [], 'products'],
            'empty products' => [fn () => ['products' => []], 'products'],
            'unknown product' => [fn () => ['products' => [['product_id' => '01a0edee-18ce-732e-b88d-ce31ddbe92d5', 'quantity' => 1]]], 'products.0.product_id'],
            'not a uuid' => [fn () => ['products' => [['product_id' => 'abc', 'quantity' => 1]]], 'products.0.product_id'],
            'zero quantity' => [fn (ProductModel $p) => ['products' => [['product_id' => $p->id, 'quantity' => 0]]], 'products.0.quantity'],
            'missing quantity' => [fn (ProductModel $p) => ['products' => [['product_id' => $p->id]]], 'products.0.quantity'],
            'repeated product' => [fn (ProductModel $p) => ['products' => [['product_id' => $p->id, 'quantity' => 1], ['product_id' => $p->id, 'quantity' => 1]]], 'products.0.product_id'],
        ];
    }

    /**
     * @param  array<string, int>  $quantities  keyed by product id
     */
    private function createOrder(array $quantities): OrderData
    {
        return $this->handle(array_map(
            fn (string $id, int $quantity) => new OrderItem($id, $quantity),
            array_keys($quantities),
            $quantities,
        ));
    }

    /**
     * @param  list<OrderItem>  $items
     */
    private function handle(array $items): OrderData
    {
        return app(CreateOrder::class)->handle(new CreateOrderDto((string) Str::uuid(), $this->user->id, $items));
    }

    /**
     * @return array<string, int>
     */
    private function lineQuantities(OrderData $order): array
    {
        return collect($order->lines)->mapWithKeys(fn (OrderLineData $line) => [$line->productId => $line->quantity])->all();
    }
}
