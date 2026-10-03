<?php

namespace Src\Ordering\Domain\Events;

/**
 * Carries only the order id: listeners load the current order themselves, so a queued
 * payload stays small and never holds a stale copy of the order.
 */
final readonly class OrderCreated
{
    public function __construct(public string $orderId) {}
}
