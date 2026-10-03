<?php

namespace Src\Ordering\Application;

use DateTimeImmutable;
use Src\Ordering\Domain\Order;
use Src\Ordering\Domain\OrderLine;

/**
 * An order as the use cases return it to the outside world.
 */
final readonly class OrderData
{
    /**
     * @param  list<OrderLineData>  $lines
     */
    public function __construct(
        public string $id,
        public int $customerId,
        public int $number,
        public string $total,
        public array $lines,
        public DateTimeImmutable $placedAt,
    ) {}

    public static function fromOrder(Order $order): self
    {
        return new self(
            $order->id,
            $order->customerId,
            $order->number,
            $order->total->toDecimal(),
            array_map(fn (OrderLine $line) => new OrderLineData(
                $line->productId,
                $line->productName,
                $line->unitPrice->toDecimal(),
                $line->quantity,
            ), $order->lines),
            $order->placedAt,
        );
    }
}
