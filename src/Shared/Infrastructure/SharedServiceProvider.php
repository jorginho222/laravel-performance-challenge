<?php

namespace Src\Shared\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Application\EventBus;
use Src\Shared\Application\TransactionManager;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        EventBus::class => LaravelEventBus::class,
        TransactionManager::class => LaravelTransactionManager::class,
    ];
}
