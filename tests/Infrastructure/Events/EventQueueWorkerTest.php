<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\AMQP\Queue\Queue;
use App\Infrastructure\Events\EventBus;
use App\Infrastructure\Events\EventQueueWorker;
use Lcobucci\Clock\Clock;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\PausedClock;

class EventQueueWorkerTest extends TestCase
{
    private EventQueueWorker $commandQueueWorker;
    private MockObject $commandBus;
    private MockObject $failedQueueFactory;
    private Clock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(EventBus::class);
        $this->failedQueueFactory = $this->createMock(FailedQueueFactory::class);
        $this->clock = PausedClock::on(new \DateTimeImmutable("2022-07-01"));

        $this->commandQueueWorker = new EventQueueWorker(
            $this->commandBus,
            $this->failedQueueFactory,
            $this->clock,
        );
    }

    public function testProcessMessageSuccess(): void
    {
        $message = $this->createMock(AMQPMessage::class);
        $command = new TestEvent();

        $this->commandBus
            ->expects($this->once())
            ->method("dispatch")
            ->with($command);

        $this->commandQueueWorker->processMessage(
            $command,
            $message,
        );
        $this->assertEquals("event-queue-worker", $this->commandQueueWorker->getName());
    }

    public function testProcessFailureSuccess(): void
    {
        $message = $this->createMock(AMQPMessage::class);
        $command = new TestEvent();
        $queue = $this->createMock(Queue::class);
        $failedQueue = $this->createMock(FailedQueue::class);

        $this->failedQueueFactory
            ->expects($this->once())
            ->method("buildFor")
            ->with($queue)
            ->willReturn($failedQueue);

        $failedQueue
            ->expects($this->once())
            ->method("queue")
            ->with($command);

        $this->commandQueueWorker->processFailure(
            $command,
            $message,
            new \RuntimeException("A grave error"),
            $queue,
        );
    }
}
