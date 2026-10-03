<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Src\Identity\Infrastructure\IdentityServiceProvider;
use Src\Ordering\Infrastructure\OrderingServiceProvider;
use Src\Shared\Infrastructure\SharedServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    IdentityServiceProvider::class,
    OrderingServiceProvider::class,
    SharedServiceProvider::class,
];
