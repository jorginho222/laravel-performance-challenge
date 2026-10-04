<?php

namespace Src\Ordering\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Ordering\Domain\Order;
use Src\Ordering\Domain\OrderLine;
use Src\Ordering\Domain\OrderRepository;
use Src\Shared\Domain\Money;

class EloquentOrderRepository implements OrderRepository
{
    /**
     * The sequence row is locked until the transaction ends.
     */
    public function nextNumber(): int
    {
        $sequence = DB::table('sequences')->where('name', 'orders')->lockForUpdate()->first();
        $number = $sequence->value + 1;

        DB::table('sequences')->where('name', 'orders')->update(['value' => $number]);

        return $number;
    }

    public function save(Order $order): void
    {
        $model = OrderModel::create([
            'id' => $order->id,
            'user_id' => $order->customerId,
            'number' => $order->number,
            'total' => $order->total->toDecimal(),
            'created_at' => $order->placedAt,
            'updated_at' => $order->placedAt,
        ]);

        $model->products()->attach(
            collect($order->lines)->mapWithKeys(fn(OrderLine $line) => [$line->productId => ['quantity' => $line->quantity]])->all()
        );
    }

    public function find(string $id): ?Order
    {
        $model = OrderModel::with('products')->find($id);

        if ($model === null) {
            return null;
        }

        return Order::reconstitute(
            $model->id,
            $model->user_id,
            $model->number,
            $model->products->map(fn(OrderableProductModel $product) => new OrderLine(
                $product->id,
                $product->name,
                Money::fromDecimal($product->price),
                (int)$product->pivot->quantity,
            ))->all(),
            Money::fromDecimal($model->total),
            DateTimeImmutable::createFromInterface($model->created_at),
        );
    }
}
