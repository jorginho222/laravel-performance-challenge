<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const COUNT = 100;

    public function run(): void
    {
        Category::factory(self::COUNT)->create();
    }
}
