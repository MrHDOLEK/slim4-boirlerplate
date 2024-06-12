<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Envelope;
use App\Infrastructure\Events\EventQueueWorker;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EventQueueTest extends TestCase
{
    private TestEventQueue $eventQueue;
    private MockObject $AMQPChannelFactory;
    private MockObject $eventQueueWorker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);
        $this->eventQueueWorker = $this->createMock(EventQueueWorker::class);

        $this->eventQueue = new TestEventQueue(
            $this->AMQPChannelFactory,
            $this->eventQueueWorker,
        );
    }

    public function testGetWorkerSuccess(): void
    {
        $this->assertInstanceOf(EventQueueWorker::class, $this->eventQueue->getWorker());
    }

    public function testQueueSuccess(): void
    {
        $event = new TestEvent();
        $amqpChannel = $this->createMock(AMQPChannel::class);

        $this->AMQPChannelFactory
            ->expects($this->once())
            ->method("getForQueue")
            ->with($this->eventQueue)
            ->willReturn($amqpChannel);

        $properties =
            [
                "content_type" => "text/plain",
                "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                "expiration" => 43200000,
                "application_headers" => new AMQPTable([
                    "x-retry-count" => 0,
                ]),
            ];
        $message = new AMQPMessage(serialize($event), $properties);

        $amqpChannel
            ->expects($this->once())
            ->method("basic_publish")
            ->with($message, "", "test-command-queue");

        $this->eventQueue->queue($event);
    }

    public function testQueueItShouldThrowWhenInvalidEnvelope(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue "test-command-queue" requires a event to be queued, Envelope given');

        $this->eventQueue->queue($this->getMockBuilder(Envelope::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMockClassName("Envelope")
            ->getMock());
    }

    public function testQueueBatchSuccess(): void
    {
        $event = new TestEvent();
        $amqpChannel = $this->createMock(AMQPChannel::class);

        $this->AMQPChannelFactory
            ->expects($this->once())
            ->method("getForQueue")
            ->with($this->eventQueue)
            ->willReturn($amqpChannel);

        $properties = [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => 43200000,
            "application_headers" => new AMQPTable([
                "x-retry-count" => 0,
            ]),
        ];
        $message = new AMQPMessage(serialize($event), $properties);

        $amqpChannel
            ->expects($this->once())
            ->method("batch_basic_publish")
            ->with($message, "", "test-command-queue");

        $amqpChannel
            ->expects($this->once())
            ->method("publish_batch");

        $this->eventQueue->queueBatch([$event]);
    }

    public function testQueueBatchItShouldThrowWhenInvalidEnvelope(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue "test-command-queue" requires a event to be queued, Envelope given');

        $this->eventQueue->queueBatch([$this->getMockBuilder(Envelope::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMockClassName("Envelope")
            ->getMock(), ]);
    }
}
