<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\DelayedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\AMQPChannelOptions;
use App\Infrastructure\AMQP\Queue\DelayedQueue\DelayedQueue;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Infrastructure\AMQP\Queue\TestQueue;
use Tests\Infrastructure\AMQP\RunUnitTester\RunUnitTester;

class DelayedQueueTest extends TestCase
{
    private MockObject $AMQPChannelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);
    }

    public function testGetNameSuccess(): void
    {
        $delayedQueue = new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            10,
            $this->AMQPChannelFactory,
        );

        $this->assertEquals("delayed-10s-test-queue", $delayedQueue->getName());
        $this->assertEquals(0, $delayedQueue->getNumberOfConsumers());
    }

    public function testQueueSuccess(): void
    {
        $delayedQueue = new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            10,
            $this->AMQPChannelFactory,
        );

        $options = new AMQPChannelOptions(false, true, false, false, false, [
            "x-dead-letter-exchange" => ["S", "dlx"],
            "x-dead-letter-routing-key" => ["S", "test-queue"],
            "x-message-ttl" => ["I", 10000],
            "x-expires" => ["I", 10000 + 100000], // Keep the Q for 100s after the last message,
        ]);

        $this->AMQPChannelFactory
            ->expects($this->once())
            ->method("getForQueue")
            ->with($delayedQueue, $options);

        $delayedQueue->queue(new RunUnitTester());
    }

    public function testGetWorkerSuccess(): void
    {
        $delayedQueue = new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            10,
            $this->AMQPChannelFactory,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Delayed queues do not have workers");

        $delayedQueue->getWorker();
    }

    public function testItShouldThrowWhenInvalidSeconds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Delay cannot be less than 1 second");

        new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            0,
            $this->AMQPChannelFactory,
        );
    }
}
