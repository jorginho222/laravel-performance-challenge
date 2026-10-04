<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Identity\Infrastructure\Persistence\User;
use Tests\TestCase;

class InactiveProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private ProductModel $active;

    private ProductModel $inactive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->active = ProductModel::factory()->create(['name' => 'active widget']);
        $this->inactive = ProductModel::factory()->create(['name' => 'inactive widget', 'status' => 'inactive']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function listings(): array
    {
        return [
            'database listing' => ['/api/products'],
            'search' => ['/api/products?search=widget'],
        ];
    }

    #[DataProvider('listings')]
    public function test_customers_only_list_active_products(string $url): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->active->id);
    }

    #[DataProvider('listings')]
    public function test_admins_list_inactive_products_too(string $url): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson($url)->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_customers_cannot_filter_by_inactive_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/products?status=inactive')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_admins_filter_by_inactive_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/products?status=inactive')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->inactive->id);
    }

    public function test_an_inactive_product_is_not_found_for_customers(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/products/{$this->inactive->id}")->assertNotFound();
        $this->getJson("/api/products/{$this->active->id}")->assertOk();
    }

    public function test_admins_see_an_inactive_product(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/products/{$this->inactive->id}")->assertOk();
    }

    public function test_the_products_page_only_lists_active_products_for_customers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/products')
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $this->active->id));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/products')
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 2));
    }
}
