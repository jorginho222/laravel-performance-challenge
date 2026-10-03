<?php

namespace Src\Ordering\Application\CreateOrder;

final readonly class CreateOrderCommand
{
    /**
     * @param  list<OrderItem>  $items
     */
    public function __construct(
        public int $customerId,
        public array $items,
    ) {}
}
