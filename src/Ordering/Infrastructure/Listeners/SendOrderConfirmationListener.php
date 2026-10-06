<?php

namespace Src\Ordering\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Src\Ordering\Application\SendOrderConfirmation\SendOrderConfirmation;
use Src\Ordering\Domain\Events\OrderCreated;
use Throwable;

/**
 * Queued (Redis): sends the confirmation email of a new order. After all attempts fail,
 * the job is stored in the failed_jobs table (see `queue:failed` and `queue:retry`).
 */
class SendOrderConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    // A queued listener only receives the event in handle(), so dependencies are injected here.
    public function __construct(private SendOrderConfirmation $sendConfirmation) {}

    public function handle(OrderCreated $event): void
    {
        $this->sendConfirmation->handle($event->orderId);
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error("Order confirmation email for order {$event->orderId} failed.", [
            'order_id' => $event->orderId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
