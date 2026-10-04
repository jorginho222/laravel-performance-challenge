<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;
use Src\Catalog\Infrastructure\Persistence\ProductModel;
use Src\Identity\Infrastructure\Persistence\User;
use Src\Ordering\Application\CreateOrder\CreateOrder;
use Src\Ordering\Application\CreateOrder\CreateOrderDto;
use Src\Ordering\Application\CreateOrder\OrderItem;
use Tests\TestCase;

class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_product_listing_shows_products_filters_categories_and_abilities(): void
    {
        $product = ProductModel::factory()->create(['name' => 'blue widget']);

        $this->actingAs(User::factory()->create())
            ->get("/products?category_id={$product->category_id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Products/ProductIndex')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'blue widget')
                ->where('products.data.0.category.id', $product->category_id)
                ->has('products.links')
                ->has('products.meta')
                ->where('filters.category_id', $product->category_id)
                ->has('categories', 1)
                ->where('auth.can.manageCatalog', false)
                ->where('auth.can.placeOrders', true));
    }

    public function test_page_links_keep_the_filters(): void
    {
        $category = CategoryModel::factory()->create();
        ProductModel::factory()->count(20)->for($category, 'category')->create();

        $this->actingAs(User::factory()->create())
            ->get("/products?category_id={$category->id}&status=active")
            ->assertInertia(fn (Assert $page) => $page->where('products.links.next', fn (string $next) => str_contains($next, "category_id={$category->id}")
                && str_contains($next, 'status=active')));
    }

    public function test_customers_cannot_open_the_product_forms(): void
    {
        $product = ProductModel::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get('/products/create')->assertForbidden();
        $this->get("/products/{$product->id}/edit")->assertForbidden();
        $this->get('/categories/create')->assertForbidden();
    }

    public function test_admins_create_a_product(): void
    {
        $category = CategoryModel::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/products/create')->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Products/ProductCreate')
            ->has('categories', 1));

        $this->post('/products', [
            'name' => 'fresh widget',
            'category_id' => $category->id,
            'price' => '9.99',
            'stock' => 3,
            'status' => 'active',
        ])->assertRedirect('/products')->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseHas('products', ['name' => 'fresh widget', 'price' => '9.99']);
    }

    public function test_invalid_product_data_returns_to_the_form_with_errors(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->from('/products/create')
            ->post('/products', ['name' => ''])
            ->assertRedirect('/products/create')
            ->assertSessionHasErrors(['name', 'category_id', 'price', 'stock', 'status']);
    }

    public function test_admins_edit_a_product(): void
    {
        $product = ProductModel::factory()->create(['stock' => 1]);
        $this->actingAs(User::factory()->admin()->create());

        $this->get("/products/{$product->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Products/ProductEdit')
            ->where('product.id', $product->id));

        $this->put("/products/{$product->id}", ['stock' => 7])->assertRedirect('/products');

        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_a_product_with_orders_is_not_deleted(): void
    {
        $product = ProductModel::factory()->create(['stock' => 10]);
        app(CreateOrder::class)->handle(new CreateOrderDto((string) Str::uuid(), User::factory()->create()->id, [new OrderItem($product->id, 1)]));

        $this->actingAs(User::factory()->admin()->create())
            ->from('/products')
            ->delete("/products/{$product->id}")
            ->assertRedirect('/products')
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertModelExists($product);
    }

    public function test_admins_manage_categories(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/categories', ['name' => 'books'])->assertRedirect('/categories');
        $category = CategoryModel::where('name', 'books')->firstOrFail();

        $this->get("/categories/{$category->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Categories/CategoryEdit')
            ->where('category.name', 'books'));

        $this->put("/categories/{$category->id}", ['name' => 'novels'])->assertRedirect('/categories');
        $this->assertSame('novels', $category->fresh()->name);

        $this->from('/categories')->delete("/categories/{$category->id}")->assertRedirect('/categories');
        $this->assertModelMissing($category);
    }

    public function test_the_category_listing_is_paginated(): void
    {
        CategoryModel::factory()->count(20)->create();

        $this->actingAs(User::factory()->create())
            ->get('/categories?page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Categories/CategoryIndex')
                ->has('categories.data', 5)
                ->where('categories.meta.current_page', 2)
                ->where('categories.links.prev', fn (string $prev) => str_contains($prev, '/categories?page=1')));
    }
}
