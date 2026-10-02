<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderConfirmationMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Order #{$this->order->number} confirmed");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.confirmation');
    }
}
