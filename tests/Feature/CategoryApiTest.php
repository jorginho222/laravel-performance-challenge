<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/categories', ['name' => 'Books'])
            ->assertCreated()->assertJsonPath('data.name', 'Books')->json('data.id');

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/categories/$id")->assertOk();
        $this->putJson("/api/categories/$id", ['name' => 'Games'])->assertOk()->assertJsonPath('data.name', 'Games');
        $this->deleteJson("/api/categories/$id")->assertNoContent();
        $this->getJson("/api/categories/$id")->assertNotFound();
    }

    public function test_validates_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', [])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_cannot_delete_category_with_products(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create();

        $this->deleteJson("/api/categories/{$product->category_id}")->assertConflict();
    }
}
