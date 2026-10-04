<?php

namespace Tests\Unit\Ordering;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Ordering\Domain\Events\OrderCreated;
use Src\Ordering\Domain\Order;
use Src\Ordering\Domain\OrderLine;
use Src\Shared\Domain\Money;

class OrderTest extends TestCase
{
    public function test_placing_an_order_totals_its_lines(): void
    {
        $order = $this->place([
            new OrderLine('p1', 'widget', Money::fromDecimal('10.10'), 3),
            new OrderLine('p2', 'gadget', Money::fromDecimal('0.35'), 2),
        ]);

        $this->assertSame('31.00', $order->total->toDecimal());
    }

    public function test_placing_an_order_records_order_created_once(): void
    {
        $order = $this->place([new OrderLine('p1', 'widget', Money::fromDecimal('1.00'), 1)]);

        $this->assertEquals([new OrderCreated('order-1')], $order->pullDomainEvents());
        $this->assertSame([], $order->pullDomainEvents());
    }

    public function test_a_reconstituted_order_records_no_events(): void
    {
        $order = Order::reconstitute('order-1', 'customer-1', 1, [], Money::zero(), new DateTimeImmutable);

        $this->assertSame([], $order->pullDomainEvents());
    }

    public function test_an_order_needs_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->place([]);
    }

    public function test_a_line_needs_a_positive_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrderLine('p1', 'widget', Money::fromDecimal('1.00'), 0);
    }

    /**
     * @param  list<OrderLine>  $lines
     */
    private function place(array $lines): Order
    {
        return Order::place('order-1', 'customer-1', 1, $lines, new DateTimeImmutable);
    }
}
