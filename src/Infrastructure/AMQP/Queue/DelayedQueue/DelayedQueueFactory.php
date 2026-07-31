<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue\DelayedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Transport;

class DelayedQueueFactory
{
    public function __construct(
        private readonly AMQPChannelFactory $AMQPChannelFactory,
        private readonly MessageSerializer $serializer,
        private readonly FailedQueueFactory $failedQueueFactory,
    ) {}

    public function buildWithDelayForQueue(int $delayInSeconds, Transport $queue): Transport
    {
        return new DelayedQueue(
            $queue,
            $delayInSeconds,
            $this->AMQPChannelFactory,
            $this->serializer,
            $this->failedQueueFactory,
        );
    }
}
