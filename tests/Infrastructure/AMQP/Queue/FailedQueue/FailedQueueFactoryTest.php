<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\FailedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Infrastructure\AMQP\Queue\TestQueue;

class FailedQueueFactoryTest extends TestCase
{
    private FailedQueueFactory $failedQueueFactory;
    private MockObject $AMQPChannelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);

        $this->failedQueueFactory = new FailedQueueFactory(
            $this->AMQPChannelFactory,
            new NativePhpMessageSerializer(),
        );
    }

    public function testBuildFor(): void
    {
        $queue = new TestQueue($this->AMQPChannelFactory);
        $expectedFailedQueue = new FailedQueue($queue, $this->AMQPChannelFactory, new NativePhpMessageSerializer(), $this->failedQueueFactory);

        $this->assertEquals(
            $expectedFailedQueue,
            $this->failedQueueFactory->buildFor($queue),
        );
    }
}
