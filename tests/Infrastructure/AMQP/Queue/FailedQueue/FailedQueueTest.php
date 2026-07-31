<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\FailedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Infrastructure\AMQP\Queue\TestQueue;

class FailedQueueTest extends TestCase
{
    private FailedQueue $failedQueue;
    private TestQueue $testQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);
        $this->testQueue = new TestQueue($AMQPChannelFactory);

        $serializer = new NativePhpMessageSerializer();

        $this->failedQueue = new FailedQueue(
            $this->testQueue,
            $AMQPChannelFactory,
            $serializer,
            new FailedQueueFactory($AMQPChannelFactory, $serializer),
        );
    }

    public function testGetNameSuccess(): void
    {
        $this->assertEquals("test-queue-failed", $this->failedQueue->getName());
        $this->assertEquals(0, $this->failedQueue->getNumberOfConsumers());
    }

    public function testGetWorkerSuccess(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed queues do not have workers");

        $this->failedQueue->getWorker();
    }
}
