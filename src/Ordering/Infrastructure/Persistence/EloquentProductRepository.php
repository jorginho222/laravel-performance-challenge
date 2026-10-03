<?php

namespace Src\Ordering\Infrastructure\Persistence;

use Src\Ordering\Domain\Product;
use Src\Ordering\Domain\ProductRepository;
use Src\Shared\Domain\Money;

class EloquentProductRepository implements ProductRepository
{
    public function lockByIds(array $ids): array
    {
        return OrderableProductModel::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->map(fn (OrderableProductModel $model) => new Product(
                $model->id,
                $model->name,
                Money::fromDecimal($model->price),
                $model->stock,
                $model->status === 'active',
            ))
            ->all();
    }

    public function save(Product $product): void
    {
        OrderableProductModel::whereKey($product->id)->update(['stock' => $product->stock()]);
    }
}
