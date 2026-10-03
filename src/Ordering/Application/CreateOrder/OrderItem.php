<?php

namespace Src\Ordering\Application\CreateOrder;

final readonly class OrderItem
{
    public function __construct(
        public string $productId,
        public int $quantity,
    ) {}
}
