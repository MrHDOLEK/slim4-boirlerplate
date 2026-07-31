<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queues;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Attribute\AsAmqpQueue;
use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Events\EventQueueWorker;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Worker;

#[AsAmqpQueue(name: "user-command-queue", numberOfWorkers: 1)]
class UserEventQueue extends AmqpQueue
{
    public function __construct(
        AMQPChannelFactory $AMQPChannelFactory,
        MessageSerializer $serializer,
        FailedQueueFactory $failedQueueFactory,
        private readonly EventQueueWorker $worker,
    ) {
        parent::__construct($AMQPChannelFactory, $serializer, $failedQueueFactory);
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }
}
