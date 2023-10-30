<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing;

abstract class AggregateRoot
{
    /** @var array<\App\Infrastructure\Eventing\DomainEvent> */
    private array $recordedEvents = [];

    /**
     * @return array<\App\Infrastructure\Eventing\DomainEvent>
     */
    public function getRecordedEvents(): array
    {
        $recordedEvents = $this->recordedEvents;
        $this->recordedEvents = [];

        return $recordedEvents;
    }

    protected function recordThat(DomainEvent $domainEvent): void
    {
        $this->recordedEvents[] = $domainEvent;
    }
}
