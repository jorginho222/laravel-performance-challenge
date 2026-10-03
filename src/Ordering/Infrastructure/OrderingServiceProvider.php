<?php

namespace Src\Ordering\Infrastructure;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\Ordering\Application\SendOrderConfirmation\CustomerDirectory;
use Src\Ordering\Application\SendOrderConfirmation\OrderConfirmationMailer;
use Src\Ordering\Domain\Events\OrderCreated;
use Src\Ordering\Domain\OrderRepository;
use Src\Ordering\Domain\ProductRepository;
use Src\Ordering\Infrastructure\Listeners\SendOrderConfirmationEmail;
use Src\Ordering\Infrastructure\Mail\LaravelOrderConfirmationMailer;
use Src\Ordering\Infrastructure\Persistence\DatabaseCustomerDirectory;
use Src\Ordering\Infrastructure\Persistence\EloquentOrderRepository;
use Src\Ordering\Infrastructure\Persistence\EloquentProductRepository;

class OrderingServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        OrderRepository::class => EloquentOrderRepository::class,
        ProductRepository::class => EloquentProductRepository::class,
        CustomerDirectory::class => DatabaseCustomerDirectory::class,
        OrderConfirmationMailer::class => LaravelOrderConfirmationMailer::class,
    ];

    public function boot(): void
    {
        Event::listen(OrderCreated::class, SendOrderConfirmationEmail::class);
    }
}
