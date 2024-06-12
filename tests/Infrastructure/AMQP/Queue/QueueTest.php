<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Infrastructure\AMQP\RunUnitTester\RunUnitTester;

class QueueTest extends TestCase
{
    private TestQueue $testQueue;
    private MockObject $AMQPChannelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);

        $this->testQueue = new TestQueue(
            $this->AMQPChannelFactory,
        );
    }

    public function testQueueSuccess(): void
    {
        $envelope = new RunUnitTester();

        $channel = $this->createMock(AMQPChannel::class);
        $this->AMQPChannelFactory
            ->expects($this->once())
            ->method("getForQueue")
            ->with($this->testQueue, null)
            ->willReturn($channel);

        $properties = [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => 43200000,
            "application_headers" => new AMQPTable([
                "x-retry-count" => 0,
            ]),
        ];
        $message = new AMQPMessage(serialize($envelope), $properties);

        $channel
            ->expects($this->once())
            ->method("basic_publish")
            ->with($message, null, $this->testQueue->getName());

        $this->testQueue->queue($envelope);
    }

    public function testQueueBatchSuccess(): void
    {
        $envelope = new RunUnitTester();

        $channel = $this->createMock(AMQPChannel::class);
        $this->AMQPChannelFactory
            ->expects($this->once())
            ->method("getForQueue")
            ->with($this->testQueue, null)
            ->willReturn($channel);

        $properties = [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => 43200000,
            "application_headers" => new AMQPTable([
                "x-retry-count" => 0,
            ]),
        ];
        $message = new AMQPMessage(serialize($envelope), $properties);

        $channel
            ->expects($this->exactly(2))
            ->method("batch_basic_publish")
            ->with($message, null, $this->testQueue->getName());

        $channel
            ->expects($this->once())
            ->method("publish_batch");

        $this->testQueue->queueBatch([$envelope, $envelope]);
    }

    public function testQueueBatchWhenEmpty(): void
    {
        $channel = $this->createMock(AMQPChannel::class);
        $this->AMQPChannelFactory
            ->expects($this->never())
            ->method("getForQueue");

        $channel
            ->expects($this->never())
            ->method("batch_basic_publish");

        $channel
            ->expects($this->never())
            ->method("publish_batch");

        $this->testQueue->queueBatch([]);
    }

    public function testQueueBatchItShouldThrowWhenInvalidEnvelope(): void
    {
        $channel = $this->createMock(AMQPChannel::class);
        $this->AMQPChannelFactory
            ->expects($this->never())
            ->method("getForQueue");

        $channel
            ->expects($this->never())
            ->method("batch_basic_publish");

        $channel
            ->expects($this->never())
            ->method("publish_batch");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('All envelopes need to implement App\Infrastructure\AMQP\Envelope');

        /** @phpstan-ignore-next-line */
        $this->testQueue->queueBatch(["test"]);
    }

    public function testGetNameItShouldThrowWhenNoAttribute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("AsAmqpQueue attribute not set");

        $queue = new TestQueueWithoutAttribute($this->createMock(AMQPChannelFactory::class));
        $queue->getName();
    }

    public function testGetNumberOfWorkersItShouldThrowWhenNoAttribute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("AsAmqpQueue attribute not set");

        $queue = new TestQueueWithoutAttribute($this->createMock(AMQPChannelFactory::class));
        $queue->getNumberOfConsumers();
    }
}
