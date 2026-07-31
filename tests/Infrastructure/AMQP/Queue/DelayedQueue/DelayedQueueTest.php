<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\DelayedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\AMQPChannelOptions;
use App\Infrastructure\AMQP\Queue\DelayedQueue\DelayedQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Infrastructure\AMQP\Queue\TestQueue;
use Tests\Support\RunUnitTester;

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
            new NativePhpMessageSerializer(),
            $this->failedQueueFactory(),
        );

        $this->assertEquals("delayed-10s-test-queue", $delayedQueue->getName());
        $this->assertEquals(0, $delayedQueue->getNumberOfConsumers());
    }

    public function testSendSuccess(): void
    {
        $delayedQueue = new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            10,
            $this->AMQPChannelFactory,
            new NativePhpMessageSerializer(),
            $this->failedQueueFactory(),
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

        $delayedQueue->send(new RunUnitTester());
    }

    public function testGetWorkerSuccess(): void
    {
        $delayedQueue = new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            10,
            $this->AMQPChannelFactory,
            new NativePhpMessageSerializer(),
            $this->failedQueueFactory(),
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
            new NativePhpMessageSerializer(),
            $this->failedQueueFactory(),
        );
    }

    private function failedQueueFactory(): FailedQueueFactory
    {
        return new FailedQueueFactory($this->AMQPChannelFactory, new NativePhpMessageSerializer());
    }
}
