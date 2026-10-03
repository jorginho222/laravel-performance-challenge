<?php

namespace Src\Shared\Domain;

/**
 * Records the domain events raised by an aggregate until the application layer publishes them
 * (after the transaction that persists the aggregate is committed).
 */
abstract class AggregateRoot
{
    /** @var list<object> */
    private array $domainEvents = [];

    protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
