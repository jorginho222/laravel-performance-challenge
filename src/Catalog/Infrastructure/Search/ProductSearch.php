<?php

namespace Src\Catalog\Infrastructure\Search;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Src\Catalog\Infrastructure\Persistence\ProductModel;

class ProductSearch
{
    /**
     * Paginate products: full-text search through Meilisearch (ranked by relevance,
     * page-number pagination) when a search term is given, otherwise a plain database
     * listing ordered by name with cursor pagination. Page links keep the filters.
     *
     * @param  array{search?: string, category_id?: string, status?: string}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator|CursorPaginator
    {
        return isset($filters['search'])
            ? $this->search($filters)
            : $this->list($filters);
    }

    /**
     * @param  array{search: string, category_id?: string, status?: string}  $filters
     */
    private function search(array $filters): LengthAwarePaginator
    {
        return ProductModel::search($filters['search'])
            ->when($filters['category_id'] ?? null, fn ($s, $v) => $s->where('category_id', $v))
            ->when($filters['status'] ?? null, fn ($s, $v) => $s->where('status', $v))
            ->query(fn ($q) => $q->with('category'))
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param  array{category_id?: string, status?: string}  $filters
     */
    private function list(array $filters): CursorPaginator
    {
        return ProductModel::with('category')
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate()
            ->withQueryString();
    }
}
