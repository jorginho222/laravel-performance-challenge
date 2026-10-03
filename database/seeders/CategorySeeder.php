<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Catalog\Infrastructure\Persistence\CategoryModel;

class CategorySeeder extends Seeder
{
    public const COUNT = 100;

    public function run(): void
    {
        CategoryModel::factory(self::COUNT)->create();
    }
}
