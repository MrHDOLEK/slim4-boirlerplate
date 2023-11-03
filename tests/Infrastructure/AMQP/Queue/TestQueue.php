<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\AMQP\Worker\Worker;
use App\Infrastructure\Attribute\AsAmqpQueue;
use DateTimeImmutable;
use Tests\Infrastructure\AMQP\Worker\TestWorker;
use Tests\PausedClock;

#[AsAmqpQueue(name: "test-queue", numberOfWorkers: 1)]
class TestQueue extends AmqpQueue
{
    public function getWorker(): Worker
    {
        return new TestWorker(PausedClock::on(new DateTimeImmutable("2022-07-01")));
    }
}
