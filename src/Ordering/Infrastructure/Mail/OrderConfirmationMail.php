<?php

namespace Src\Ordering\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Src\Ordering\Application\OrderData;

class OrderConfirmationMail extends Mailable
{
    public function __construct(public OrderData $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Order #{$this->order->number} confirmed");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.confirmation');
    }
}
