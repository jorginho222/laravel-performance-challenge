<?php

use App\Providers\AppServiceProvider;
use Src\Ordering\Infrastructure\OrderingServiceProvider;
use Src\Shared\Infrastructure\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    OrderingServiceProvider::class,
];
