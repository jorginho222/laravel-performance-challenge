<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Src\Catalog\Infrastructure\Persistence\Category;
use Src\Catalog\Infrastructure\Persistence\Product;

class ProductSeeder extends Seeder
{
    public const COUNT = 100000;

    private const CHUNK = 1000;

    public function run(): void
    {
        $categoryIds = Category::pluck('id');

        if ($categoryIds->isEmpty()) {
            $this->command?->warn('No categories found; run CategorySeeder first.');

            return;
        }

        $now = now();

        for ($created = 0; $created < self::COUNT; $created += self::CHUNK) {
            $rows = [];

            for ($i = 0; $i < min(self::CHUNK, self::COUNT - $created); $i++) {
                $rows[] = Product::factory()->raw([
                    'id' => (string) Str::uuid7(),
                    'category_id' => $categoryIds->random(),
                    'status' => fake()->boolean(90) ? 'active' : 'inactive',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Product::insert($rows);
        }
    }
}
