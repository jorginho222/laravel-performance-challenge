<?php

namespace Src\Ordering\Domain;

use Src\Ordering\Domain\Exceptions\ProductsUnavailable;
use Src\Shared\Domain\Money;

/**
 * A product as the ordering context sees it: something with a price and a stock that an
 * order can reserve. The catalog owns everything else about it.
 */
final class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly Money $price,
        private int $stock,
        public readonly bool $active,
    ) {}

    public function stock(): int
    {
        return $this->stock;
    }

    /**
     * Why the product cannot supply the quantity, or null when it can.
     */
    public function unavailabilityReason(int $quantity): ?string
    {
        return match (true) {
            ! $this->active => "Product {$this->name} is not active.",
            $this->stock < $quantity => "Product {$this->name} does not have enough stock.",
            default => null,
        };
    }

    /**
     * @throws ProductsUnavailable
     */
    public function reserve(int $quantity): void
    {
        if (($reason = $this->unavailabilityReason($quantity)) !== null) {
            throw new ProductsUnavailable([$this->id => $reason]);
        }

        $this->stock -= $quantity;
    }
}
