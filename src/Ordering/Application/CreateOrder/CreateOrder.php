<?php

namespace Src\Ordering\Application\CreateOrder;

use DateTimeImmutable;
use Src\Ordering\Application\OrderData;
use Src\Ordering\Domain\Exceptions\ProductNotFound;
use Src\Ordering\Domain\Exceptions\ProductsUnavailable;
use Src\Ordering\Domain\Order;
use Src\Ordering\Domain\OrderLine;
use Src\Ordering\Domain\OrderRepository;
use Src\Ordering\Domain\Product;
use Src\Ordering\Domain\ProductRepository;
use Src\Ordering\Domain\StockReservation;
use Src\Shared\Application\EventBus;
use Src\Shared\Application\TransactionManager;

final class CreateOrder
{
    public function __construct(
        private OrderRepository    $orderRepository,
        private ProductRepository  $productRepository,
        private StockReservation   $stockReservation,
        private TransactionManager $transactionManager,
        private EventBus           $eventBus,
    )
    {
    }

    /**
     * Create an order from product/quantity lines, reserving the products' stock, and announce
     * it with OrderCreated. Repeated products are merged into a single line. The order id comes
     * from the client, so a resubmitted order is rejected instead of placed twice.
     *
     * @throws ProductNotFound when a product does not exist
     * @throws ProductsUnavailable when a product is not active or does not have enough stock
     */
    public function handle(CreateOrderDto $dto): OrderData
    {
        $quantities = [];
        foreach ($dto->items as $item) {
            $quantities[$item->productId] = ($quantities[$item->productId] ?? 0) + $item->quantity;
        }

        $order = $this->transactionManager->run(function () use ($dto, $quantities) {
            $products = $this->productRepository->lockByIds(array_keys($quantities));

            $missing = array_diff(array_keys($quantities), array_map(fn(Product $p) => $p->id, $products));
            if ($missing !== []) {
                throw new ProductNotFound(array_values($missing));
            }

            $this->stockReservation->reserve($products, $quantities);

            $order = Order::place(
                $dto->orderId,
                $dto->customerId,
                $this->orderRepository->nextNumber(),
                array_map(fn(Product $p) => OrderLine::forProduct($p, $quantities[$p->id]), $products),
                new DateTimeImmutable('@' . time()),
            );

            foreach ($products as $product) {
                $this->productRepository->save($product);
            }
            $this->orderRepository->save($order);

            return $order;
        });

        $this->eventBus->publish(...$order->pullDomainEvents());

        return OrderData::fromOrder($order);
    }
}
