<?php

namespace Src\Ordering\Application\SendOrderConfirmation;

use Src\Ordering\Application\OrderData;
use Src\Ordering\Domain\OrderRepository;

final class SendOrderConfirmation
{
    public function __construct(
        private OrderRepository $orders,
        private CustomerDirectory $customers,
        private OrderConfirmationMailer $mailer,
    ) {}

    /**
     * Send the confirmation email of an order to its customer. A deleted order (or customer)
     * has nobody to confirm to, so nothing is sent.
     */
    public function handle(string $orderId): void
    {
        $order = $this->orders->find($orderId);
        $email = $order ? $this->customers->emailOf($order->customerId) : null;

        if ($email === null) {
            return;
        }

        $this->mailer->send($email, OrderData::fromOrder($order));
    }
}
