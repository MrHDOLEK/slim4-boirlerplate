<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue\DelayedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Queue\DelayedQueue\DelayedQueue;
use App\Infrastructure\AMQP\Queue\DelayedQueue\DelayedQueueFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Infrastructure\AMQP\Queue\TestQueue;

class DelayedQueueFactoryTest extends TestCase
{
    private DelayedQueueFactory $delayedQueueFactory;
    private MockObject $AMQPChannelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);

        $this->delayedQueueFactory = new DelayedQueueFactory(
            $this->AMQPChannelFactory,
        );
    }

    public function testBuildWithDelayForQueueSuccess(): void
    {
        $this->assertInstanceOf(
            DelayedQueue::class,
            $this->delayedQueueFactory->buildWithDelayForQueue(10, new TestQueue($this->AMQPChannelFactory)),
        );

        $this->assertEquals(new DelayedQueue(
            new TestQueue($this->AMQPChannelFactory),
            60,
            $this->AMQPChannelFactory,
        ), $this->delayedQueueFactory->buildWithDelayForQueue(60, new TestQueue($this->AMQPChannelFactory)));
    }
}
