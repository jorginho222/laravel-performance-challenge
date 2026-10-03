<?php

namespace Src\Ordering\Domain;

use InvalidArgumentException;
use Src\Shared\Domain\Money;

final readonly class OrderLine
{
    public function __construct(
        public string $productId,
        public string $productName,
        public Money $unitPrice,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('An order line needs a quantity of at least 1.');
        }
    }

    public static function forProduct(Product $product, int $quantity): self
    {
        return new self($product->id, $product->name, $product->price, $quantity);
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
