<?php

namespace App\UseCases;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;

class BuildOrderConfirmationEmail
{
    /**
     * Build the confirmation email of an order. It only builds the message; sending it
     * is up to EmailSender.
     */
    public function handle(Order $order): OrderConfirmationMail
    {
        return new OrderConfirmationMail($order->loadMissing('products'));
    }
}
