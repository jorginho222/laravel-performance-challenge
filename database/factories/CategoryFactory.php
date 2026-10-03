<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;

/**
 * @extends Factory<CategoryModel>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true)];
    }
}
