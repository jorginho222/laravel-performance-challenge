<?php

namespace Src\Ordering\Application\SendOrderConfirmation;

use Src\Ordering\Application\OrderData;

interface OrderConfirmationMailer
{
    public function send(string $email, OrderData $order): void;
}
