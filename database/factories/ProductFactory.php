<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;
use Src\Catalog\Infrastructure\Persistence\ProductModel;

/**
 * @extends Factory<ProductModel>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category_id' => CategoryModel::factory(),
            'price' => fake()->randomFloat(2, 1, 500),
            'stock' => fake()->numberBetween(0, 100),
            'status' => 'active',
        ];
    }
}
