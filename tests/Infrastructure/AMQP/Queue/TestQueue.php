<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Attribute\AsAmqpQueue;
use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use App\Infrastructure\Messaging\Worker;
use DateTimeImmutable;
use Tests\Infrastructure\AMQP\Worker\TestWorker;
use Tests\PausedClock;

#[AsAmqpQueue(name: "test-queue", numberOfWorkers: 1)]
class TestQueue extends AmqpQueue
{
    public function __construct(
        AMQPChannelFactory $AMQPChannelFactory,
        private readonly ?Worker $worker = null,
        ?MessageSerializer $serializer = null,
        ?FailedQueueFactory $failedQueueFactory = null,
    ) {
        $serializer ??= new NativePhpMessageSerializer();

        parent::__construct(
            $AMQPChannelFactory,
            $serializer,
            $failedQueueFactory ?? new FailedQueueFactory($AMQPChannelFactory, $serializer),
        );
    }

    public function getWorker(): Worker
    {
        return $this->worker ?? new TestWorker(PausedClock::on(new DateTimeImmutable("2022-07-01")));
    }
}
