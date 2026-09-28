<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_crud(): void
    {
        $category = Category::factory()->create();

        $id = $this->postJson('/api/products', [
            'name' => 'Widget', 'category_id' => $category->id,
            'price' => 12.5, 'stock' => 4, 'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.price', '12.50')
            ->assertJsonPath('data.category.id', $category->id)
            ->json('data.id');

        $this->getJson("/api/products/$id")->assertOk();
        $this->patchJson("/api/products/$id", ['stock' => 9])->assertOk()->assertJsonPath('data.stock', 9);
        $this->deleteJson("/api/products/$id")->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_validation(): void
    {
        $this->postJson('/api/products', [
            'name' => '', 'category_id' => fake()->uuid(), 'price' => -1, 'stock' => 1.5, 'status' => 'nope',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock', 'status']);
    }

    public function test_index_filters(): void
    {
        $a = Product::factory()->create(['name' => 'Red shirt', 'status' => 'active']);
        Product::factory()->create(['name' => 'Blue shirt', 'status' => 'inactive']);

        $this->getJson('/api/products')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?status=active')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $a->id);
        $this->getJson('/api/products?search=Blue')->assertJsonCount(1, 'data');
        $this->getJson("/api/products?category_id={$a->category_id}")->assertJsonCount(1, 'data');
    }

    public function test_cannot_delete_product_in_an_order(): void
    {
        $product = Product::factory()->create();
        Order::create(['total' => 1])->products()->attach($product, ['quantity' => 1]);

        $this->deleteJson("/api/products/{$product->id}")->assertConflict();
    }
}
