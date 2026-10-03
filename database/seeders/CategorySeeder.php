<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Catalog\Infrastructure\Persistence\Category;

class CategorySeeder extends Seeder
{
    public const COUNT = 100;

    public function run(): void
    {
        Category::factory(self::COUNT)->create();
    }
}
