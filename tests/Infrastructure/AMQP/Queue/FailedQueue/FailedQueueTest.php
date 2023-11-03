<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\FailedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueue;
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

        $this->failedQueue = new FailedQueue(
            $this->testQueue,
            $AMQPChannelFactory,
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
