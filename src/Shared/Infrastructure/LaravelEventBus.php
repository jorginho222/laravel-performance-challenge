<?php

namespace Src\Shared\Infrastructure;

use Illuminate\Support\Facades\Event;
use Src\Shared\Application\EventBus;

/**
 * Publishes domain events through Laravel's dispatcher, so listeners (queued or not) are
 * registered as usual in the context's service provider.
 */
class LaravelEventBus implements EventBus
{
    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            Event::dispatch($event);
        }
    }
}
