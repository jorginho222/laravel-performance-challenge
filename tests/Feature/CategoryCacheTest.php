<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_listing_returns_categories_ordered_by_name_with_pagination_meta(): void
    {
        Category::factory()->create(['name' => 'b category']);
        Category::factory()->create(['name' => 'a category']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'a category')
            ->assertJsonPath('data.1.name', 'b category')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'created_at', 'updated_at']], 'links', 'meta']);
    }

    public function test_listing_is_served_from_cache_until_the_ttl_expires(): void
    {
        Category::factory()->create(['name' => 'a category']);

        $this->getJson('/api/categories')->assertJsonCount(1, 'data');

        // Changes are not invalidated: the cached listing is still served.
        Category::factory()->create(['name' => 'b category']);
        $this->getJson('/api/categories')->assertJsonCount(1, 'data');

        $this->travel(config('cache.categories.ttl') + 1)->seconds();

        $this->getJson('/api/categories')->assertJsonCount(2, 'data');
    }

    public function test_each_page_is_cached_separately(): void
    {
        Category::factory()->count(20)->sequence(fn ($s) => ['name' => sprintf('category %02d', $s->index)])->create();

        $this->getJson('/api/categories')->assertJsonCount(15, 'data')->assertJsonPath('meta.current_page', 1);
        $this->getJson('/api/categories?page=2')->assertJsonCount(5, 'data')->assertJsonPath('meta.current_page', 2);
        $this->getJson('/api/categories')->assertJsonCount(15, 'data')->assertJsonPath('meta.current_page', 1);
    }
}
