<?php

namespace Src\Ordering\Application\SendOrderConfirmation;

interface CustomerDirectory
{
    public function emailOf(string $customerId): ?string;
}
