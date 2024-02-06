<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Envelope;
use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\AMQP\Worker\Worker;
use RuntimeException;

abstract class EventQueue extends AmqpQueue
{
    public function __construct(
        AMQPChannelFactory $AMQPChannelFactory,
        private readonly EventQueueWorker $eventQueueWorker,
    ) {
        parent::__construct($AMQPChannelFactory);
    }

    public function getWorker(): Worker
    {
        return $this->eventQueueWorker;
    }

    public function queue(Envelope $envelope): void
    {
        if (!$envelope instanceof DomainEvent) {
            throw new RuntimeException(sprintf('Queue "%s" requires a event to be queued, %s given', $this->getName(), $envelope::class));
        }

        parent::queue($envelope);
    }

    public function queueBatch(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            if ($envelope instanceof DomainEvent) {
                continue;
            }

            throw new RuntimeException(sprintf('Queue "%s" requires a event to be queued, %s given', $this->getName(), $envelope::class));
        }

        parent::queueBatch($envelopes);
    }
}
