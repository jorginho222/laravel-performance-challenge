<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_search_returns_only_matching_products(): void
    {
        Product::factory()->create(['name' => 'blue widget']);
        Product::factory()->create(['name' => 'red gadget']);

        $this->getJson('/api/products?search=widget')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'blue widget');
    }

    public function test_search_can_be_combined_with_category_and_status_filters(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();

        $match = Product::factory()->for($category)->create(['name' => 'blue widget', 'status' => 'active']);
        Product::factory()->for($other)->create(['name' => 'green widget', 'status' => 'active']);
        Product::factory()->for($category)->create(['name' => 'red widget', 'status' => 'inactive']);

        $this->getJson("/api/products?search=widget&category_id={$category->id}&status=active")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_results_include_the_category(): void
    {
        $product = Product::factory()->create(['name' => 'blue widget']);

        $this->getJson('/api/products?search=widget')
            ->assertOk()
            ->assertJsonPath('data.0.category.id', $product->category_id);
    }

    public function test_listing_without_search_still_uses_the_database(): void
    {
        Product::factory()->create(['name' => 'b product']);
        Product::factory()->create(['name' => 'a product']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'a product')
            ->assertJsonPath('data.1.name', 'b product');
    }

    public function test_new_products_are_searchable(): void
    {
        $this->postJson('/api/products', [
            'name' => 'fresh widget',
            'category_id' => Category::factory()->create()->id,
            'price' => 9.99,
            'stock' => 3,
            'status' => 'active',
        ])->assertCreated();

        $this->getJson('/api/products?search=fresh')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_listing_without_search_is_cursor_paginated(): void
    {
        Product::factory()->count(20)->sequence(fn ($s) => ['name' => sprintf('product %02d', $s->index)])->create();

        $first = $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('data.0.name', 'product 00')
            ->assertJsonMissingPath('meta.total');

        $cursor = $first->json('meta.next_cursor');
        $this->assertNotNull($cursor);

        $this->getJson('/api/products?cursor='.$cursor)
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.name', 'product 15')
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_cursor_pagination_does_not_skip_products_sharing_the_same_name(): void
    {
        Product::factory()->count(20)->create(['name' => 'same name']);

        $first = $this->getJson('/api/products')->assertOk();
        $second = $this->getJson('/api/products?cursor='.$first->json('meta.next_cursor'))->assertOk();

        $ids = array_merge(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id'));

        $this->assertCount(20, array_unique($ids));
    }
}
