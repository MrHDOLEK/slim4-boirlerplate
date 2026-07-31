<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Transport;
use RuntimeException;

final readonly class EventPublisher
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function transportName(): string
    {
        return $this->transport->getName();
    }

    public function publish(DomainEvent $event): void
    {
        $this->transport->send($event);
    }

    /**
     * @param array<Envelope> $envelopes
     */
    public function publishAll(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            if ($envelope instanceof DomainEvent) {
                continue;
            }

            throw new RuntimeException(sprintf('Transport "%s" requires a event to be queued, %s given', $this->transportName(), get_debug_type($envelope)));
        }

        $this->transport->sendBatch($envelopes);
    }
}
