<?php

namespace Src\Ordering\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Ordering\Application\SendOrderConfirmation\CustomerDirectory;

/**
 * Customers are the identity context's users; their table is read directly so ordering
 * does not depend on that context's classes.
 */
class DatabaseCustomerDirectory implements CustomerDirectory
{
    public function emailOf(int $customerId): ?string
    {
        return DB::table('users')->where('id', $customerId)->value('email');
    }
}
