<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\Messaging\Worker;
use DateTimeImmutable;
use Tests\Infrastructure\AMQP\Worker\TestWorker;
use Tests\PausedClock;

class TestQueueWithoutAttribute extends AmqpQueue
{
    public function getWorker(): Worker
    {
        return new TestWorker(PausedClock::on(new DateTimeImmutable("2022-07-01")));
    }
}
