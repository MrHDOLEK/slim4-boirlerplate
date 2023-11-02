<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Infrastructure\AMQP\Envelope;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\AMQP\Queue\Queue;
use App\Infrastructure\AMQP\Worker\BaseWorker;
use Lcobucci\Clock\Clock;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class EventQueueWorker extends BaseWorker
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly FailedQueueFactory $failedQueueFactory,
        Clock $clock,
    ) {
        parent::__construct($clock);
    }

    public function getName(): string
    {
        return "event-queue-worker";
    }

    public function processMessage(Envelope $envelope, AMQPMessage $message): void
    {
        /** @var DomainEvent $event */
        $event = $envelope;
        $this->eventBus->dispatch($event);
    }

    public function processFailure(Envelope $envelope, AMQPMessage $message, Throwable $exception, Queue $queue): void
    {
        /** @var DomainEvent $event */
        $event = $envelope;
        $event->setMetaData([
            "exceptionMessage" => $exception->getMessage(),
            "traceAsString" => $exception->getTraceAsString(),
        ]);

        $this->failedQueueFactory->buildFor($queue)->queue($event);
    }
}
