<?php

namespace App\UseCases;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * Create an order for a user from product/quantity lines and announce it with OrderCreated. The order number is the next value of
     * the "orders" sequence and the total is calculated from the current product prices. Each product's stock is
     * discounted by the ordered quantity.
     *
     * @param  array<int, array{product_id: string, quantity: int}>  $items
     *
     * @throws ModelNotFoundException when a product does not exist
     * @throws ValidationException when a product is not active or does not have enough stock
     */
    public function handle(User $user, array $items): Order
    {
        // Repeated products are merged, so each one is a single line of the order.
        $quantities = [];
        foreach ($items as $item) {
            $quantities[$item['product_id']] = ($quantities[$item['product_id']] ?? 0) + $item['quantity'];
        }

        $order = DB::transaction(function () use ($user, $quantities) {
            // Locked until the transaction ends, so concurrent orders cannot oversell the same stock
            // (ordered by id so concurrent orders lock rows in the same order and cannot deadlock).
            $products = Product::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get();

            if ($products->count() !== count($quantities)) {
                throw (new ModelNotFoundException)->setModel(Product::class, array_diff(array_keys($quantities), $products->modelKeys()));
            }

            $this->ensureAvailable($products, $quantities);

            foreach ($products as $product) {
                $product->decrement('stock', $quantities[$product->id]);
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
     * @param  Collection<int, Product>  $products
     * @param  array<string, int>  $quantities
     *
     * @throws ValidationException
     */
    private function ensureAvailable($products, array $quantities): void
    {
        $errors = [];
        foreach ($products as $product) {
            if ($product->status !== 'active') {
                $errors["products.{$product->id}"] = "Product {$product->name} is not active.";
            } elseif ($product->stock < $quantities[$product->id]) {
                $errors["products.{$product->id}"] = "Product {$product->name} does not have enough stock.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
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
