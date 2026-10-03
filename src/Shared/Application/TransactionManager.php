<?php

namespace Src\Shared\Application;

use Closure;

interface TransactionManager
{
    /**
     * Run the callback in a transaction: committed when it returns, rolled back when it throws.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed;
}
