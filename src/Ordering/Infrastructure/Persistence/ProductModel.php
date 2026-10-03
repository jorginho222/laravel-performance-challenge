<?php

namespace Src\Ordering\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * The ordering context's own model of the products table (the catalog owns the table): it
 * only reads what an order needs and writes the stock. It is not Searchable, so stock
 * changes do not reindex the product in Meilisearch (stock is not indexed).
 */
class ProductModel extends Model
{
    protected $table = 'products';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }
}
