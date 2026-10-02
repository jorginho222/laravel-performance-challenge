<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Carries only the order id: listeners load the current order themselves, so the queued
 * payload stays small and never holds a stale copy of the order.
 */
class OrderCreated
{
    use Dispatchable;

    public function __construct(public string $orderId) {}
}
