<?php

namespace Src\Shared\Application;

interface EventBus
{
    public function publish(object ...$events): void;
}
