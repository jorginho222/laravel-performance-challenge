<?php

namespace App\UseCases;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    /**
     * Create an order for a user from product/quantity lines and announce it with OrderCreated. The order number is the next value of
     * the "orders" sequence and the total is calculated from the current product prices.
     *
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     *
     * @throws ModelNotFoundException when a product does not exist
     */
    public function handle(User $user, array $items): Order
    {
        // Repeated products are merged, so each one is a single line of the order.
        $quantities = [];
        foreach ($items as $item) {
            $quantities[$item['product_id']] = ($quantities[$item['product_id']] ?? 0) + $item['quantity'];
        }

        $order = DB::transaction(function () use ($user, $quantities) {
            $products = Product::whereIn('id', array_keys($quantities))->get();

            if ($products->count() !== count($quantities)) {
                throw (new ModelNotFoundException)->setModel(Product::class, array_diff(array_keys($quantities), $products->modelKeys()));
            }

            $order = Order::create([
                'user_id' => $user->id,
                'number' => $this->nextNumber(),
                'total' => $this->total($products, $quantities),
            ]);

            $order->products()->attach(
                $products->mapWithKeys(fn (Product $p) => [$p->id => ['quantity' => $quantities[$p->id]]])->all()
            );

            return $order->load('products');
        });

        // Fired once the transaction is committed, so listeners never see a rolled-back order.
        OrderCreated::dispatch($order);

        return $order;
    }

    /**
     * The sequence row is locked until the transaction ends, so concurrent orders get
     * consecutive numbers and a rolled-back order does not leave a gap.
     */
    private function nextNumber(): int
    {
        $sequence = DB::table('sequences')->where('name', 'orders')->lockForUpdate()->first();
        $number = $sequence->value + 1;

        DB::table('sequences')->where('name', 'orders')->update(['value' => $number]);

        return $number;
    }

    /**
     * Summed in cents to avoid floating point errors.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<string, int>  $quantities
     */
    private function total($products, array $quantities): string
    {
        $cents = $products->sum(fn (Product $p) => (int) str_replace('.', '', $p->price) * $quantities[$p->id]);

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
