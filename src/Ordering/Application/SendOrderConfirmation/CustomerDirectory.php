<?php

namespace Src\Ordering\Application\SendOrderConfirmation;

interface CustomerDirectory
{
    public function emailOf(int $customerId): ?string;
}
