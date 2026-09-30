<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * TTL-only cache for the category listing. Categories change rarely, so changes are
 * picked up when the entry expires (cache.categories.ttl). Only plain arrays are cached:
 * cache.serializable_classes is false, so models can't be unserialized.
 */
class CategoryCache
{
    /**
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public static function remember(string $key, Closure $callback): array
    {
        return Cache::store(config('cache.categories.store'))
            ->remember("categories:{$key}", config('cache.categories.ttl'), $callback);
    }
}
