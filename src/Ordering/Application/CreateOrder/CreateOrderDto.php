<?php

namespace Src\Ordering\Application\CreateOrder;

final readonly class CreateOrderDto
{
    /**
     * @param  list<OrderItem>  $items
     */
    public function __construct(
        public int $customerId,
        public array $items,
    ) {}
}
