<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue\FailedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Transport;

class FailedQueueFactory
{
    public function __construct(
        private readonly AMQPChannelFactory $AMQPChannelFactory,
        private readonly MessageSerializer $serializer,
    ) {}

    public function buildFor(Transport $queue): Transport
    {
        return new FailedQueue(
            $queue,
            $this->AMQPChannelFactory,
            $this->serializer,
            $this,
        );
    }
}
