<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Services\EmailSender;
use App\UseCases\BuildOrderConfirmationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued (Redis): builds the confirmation email and sends it to the order's user. After
 * all attempts fail, the job is stored in the failed_jobs table (see `queue:failed` and
 * `queue:retry`).
 */
class SendOrderConfirmationEmail implements ShouldQueue
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
    public function __construct(
        private BuildOrderConfirmationEmail $buildEmail,
        private EmailSender $sender,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = Order::with('user', 'products')->find($event->orderId);

        // A deleted order has nobody to confirm to, so the job ends without retrying.
        if ($order === null) {
            return;
        }

        $this->sender->send($order->user->email, $this->buildEmail->handle($order));
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error("Order confirmation email for order {$event->orderId} failed.", [
            'order_id' => $event->orderId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
