<?php

namespace Src\Ordering\Infrastructure\Mail;

use Illuminate\Support\Facades\Mail;
use Src\Ordering\Application\OrderData;
use Src\Ordering\Application\SendOrderConfirmation\OrderConfirmationMailer;

class LaravelOrderConfirmationMailer implements OrderConfirmationMailer
{
    /**
     * Send the email right away (queueing is the caller's concern, see the listener).
     */
    public function send(string $email, OrderData $order): void
    {
        Mail::to($email)->send(new OrderConfirmationMail($order));
    }
}
