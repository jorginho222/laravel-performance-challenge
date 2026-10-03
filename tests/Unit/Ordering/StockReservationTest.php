<?php

namespace Tests\Unit\Ordering;

use PHPUnit\Framework\TestCase;
use Src\Ordering\Domain\Exceptions\ProductsUnavailable;
use Src\Ordering\Domain\Product;
use Src\Ordering\Domain\StockReservation;
use Src\Shared\Domain\Money;

class StockReservationTest extends TestCase
{
    public function test_it_reserves_the_stock_of_every_product(): void
    {
        $a = $this->product('a', stock: 10);
        $b = $this->product('b', stock: 5);

        (new StockReservation)->reserve([$a, $b], ['a' => 3, 'b' => 5]);

        $this->assertSame(7, $a->stock());
        $this->assertSame(0, $b->stock());
    }

    public function test_it_reports_every_unavailable_product_and_reserves_nothing(): void
    {
        $ok = $this->product('ok', stock: 10);
        $inactive = $this->product('inactive', stock: 10, active: false);
        $short = $this->product('short', stock: 2);

        try {
            (new StockReservation)->reserve([$ok, $inactive, $short], ['ok' => 1, 'inactive' => 1, 'short' => 3]);
            $this->fail('Unavailable products should abort the reservation.');
        } catch (ProductsUnavailable $e) {
            $this->assertSame([
                'inactive' => 'Product inactive is not active.',
                'short' => 'Product short does not have enough stock.',
            ], $e->reasons);
        }

        $this->assertSame(10, $ok->stock());
    }

    public function test_a_product_never_reserves_more_than_its_stock(): void
    {
        $this->expectException(ProductsUnavailable::class);

        $this->product('a', stock: 1)->reserve(2);
    }

    private function product(string $id, int $stock, bool $active = true): Product
    {
        return new Product($id, $id, Money::fromDecimal('1.00'), $stock, $active);
    }
}
