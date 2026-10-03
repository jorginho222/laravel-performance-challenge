<?php

namespace Src\Ordering\Domain;

use Src\Ordering\Domain\Exceptions\ProductsUnavailable;

/**
 * Reserves the stock of several products at once: either all of them can supply their
 * quantity and all are reserved, or none is and every unavailable product is reported.
 */
final class StockReservation
{
    /**
     * @param  list<Product>  $products
     * @param  array<string, int>  $quantities  keyed by product id
     *
     * @throws ProductsUnavailable
     */
    public function reserve(array $products, array $quantities): void
    {
        $reasons = [];
        foreach ($products as $product) {
            if (($reason = $product->unavailabilityReason($quantities[$product->id])) !== null) {
                $reasons[$product->id] = $reason;
            }
        }

        if ($reasons !== []) {
            throw new ProductsUnavailable($reasons);
        }

        foreach ($products as $product) {
            $product->reserve($quantities[$product->id]);
        }
    }
}
