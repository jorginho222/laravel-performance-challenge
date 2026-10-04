<?php

namespace Src\Ordering\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Ordering\Domain\Events\OrderCreated;
use Src\Shared\Domain\AggregateRoot;
use Src\Shared\Domain\Money;

final class Order extends AggregateRoot
{
    /**
     * @param list<OrderLine> $lines
     */
    private function __construct(
        public readonly string            $id,
        public readonly int               $customerId,
        public readonly int               $number,
        public readonly array             $lines,
        public readonly Money             $total,
        public readonly DateTimeImmutable $placedAt,
    )
    {
    }

    /**
     * Place a new order. The total is calculated from the lines' current prices and kept
     * as it is, even if those prices change later.
     *
     * @param list<OrderLine> $lines
     */
    public static function place(string $id, int $customerId, int $number, array $lines, DateTimeImmutable $placedAt): self
    {
        if ($lines === []) {
            throw new InvalidArgumentException('An order needs at least one line.');
        }

        $total = array_reduce($lines, fn(Money $sum, OrderLine $line) => $sum->add($line->subtotal()), Money::zero());

        $order = new self($id, $customerId, $number, $lines, $total, $placedAt);
        $order->record(new OrderCreated($id));

        return $order;
    }

    /**
     * Rebuild a stored order (no event is recorded).
     *
     * @param list<OrderLine> $lines
     */
    public static function reconstitute(string $id, int $customerId, int $number, array $lines, Money $total, DateTimeImmutable $placedAt): self
    {
        return new self($id, $customerId, $number, $lines, $total, $placedAt);
    }
}
