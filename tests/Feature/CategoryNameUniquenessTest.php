<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryNameUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_creating_a_category_with_a_duplicate_name_is_rejected(): void
    {
        Category::factory()->create(['name' => 'books']);

        $this->postJson('/api/categories', ['name' => 'books'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_renaming_to_an_existing_name_is_rejected(): void
    {
        Category::factory()->create(['name' => 'books']);
        $other = Category::factory()->create(['name' => 'games']);

        $this->putJson("/api/categories/{$other->id}", ['name' => 'books'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_updating_a_category_keeping_its_own_name_is_allowed(): void
    {
        $category = Category::factory()->create(['name' => 'books']);

        $this->putJson("/api/categories/{$category->id}", ['name' => 'books'])->assertOk();
    }
}
