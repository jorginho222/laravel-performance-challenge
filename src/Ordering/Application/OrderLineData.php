<?php

namespace Src\Ordering\Application;

final readonly class OrderLineData
{
    public function __construct(
        public string $productId,
        public string $productName,
        public string $unitPrice,
        public int $quantity,
    ) {}
}
