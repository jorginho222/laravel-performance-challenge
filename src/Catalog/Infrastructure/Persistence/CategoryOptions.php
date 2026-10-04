<?php

namespace Src\Catalog\Infrastructure\Persistence;

/**
 * Every category's id and name, for selects. Not cached (unlike the listing), so a new
 * category can be chosen right away; it reads the name index only.
 */
class CategoryOptions
{
    /**
     * @return list<array{id: string, name: string}>
     */
    public static function all(): array
    {
        return CategoryModel::orderBy('name')->get(['id', 'name'])->toArray();
    }
}
