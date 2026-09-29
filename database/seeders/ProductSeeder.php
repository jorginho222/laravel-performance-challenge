<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public const COUNT = 5000;

    public function run(): void
    {
        $categoryIds = Category::pluck('id');

        if ($categoryIds->isEmpty()) {
            $this->command?->warn('No categories found; run CategorySeeder first.');

            return;
        }

        $now = now();

        collect(range(1, self::COUNT))
            ->map(fn () => Product::factory()->raw([
                'id' => (string) Str::uuid7(),
                'category_id' => $categoryIds->random(),
                'status' => fake()->boolean(90) ? 'active' : 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ]))
            ->chunk(1000)
            ->each(fn ($chunk) => Product::insert($chunk->all()));
    }
}
