<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue\FailedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\Worker;
use RuntimeException;

class FailedQueue extends AmqpQueue
{
    public function __construct(
        private readonly Transport $queue,
        AMQPChannelFactory $AMQPChannelFactory,
        MessageSerializer $serializer,
        FailedQueueFactory $failedQueueFactory,
    ) {
        parent::__construct($AMQPChannelFactory, $serializer, $failedQueueFactory);
    }

    public function getName(): string
    {
        return $this->queue->getName() . "-failed";
    }

    public function getWorker(): Worker
    {
        throw new RuntimeException("Failed queues do not have workers");
    }

    public function getNumberOfConsumers(): int
    {
        return 0;
    }
}
