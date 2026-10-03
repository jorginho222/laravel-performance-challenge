<?php

namespace Src\Ordering\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderModel extends Model
{
    protected $table = 'orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'user_id', 'number', 'total', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'number' => 'integer',
            'total' => 'decimal:2',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, 'order_product', 'order_id', 'product_id')->withPivot('quantity');
    }
}
