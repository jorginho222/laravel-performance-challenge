<?php

namespace Src\Shared\Infrastructure;

use Closure;
use Illuminate\Support\Facades\DB;
use Src\Shared\Application\TransactionManager;

class LaravelTransactionManager implements TransactionManager
{
    public function run(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
