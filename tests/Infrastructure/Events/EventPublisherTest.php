<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Events\EventPublisher;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Transport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EventPublisherTest extends TestCase
{
    private Transport&MockObject $transport;
    private EventPublisher $eventPublisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = $this->createMock(Transport::class);
        $this->transport->method("getName")->willReturn("user-command-queue");

        $this->eventPublisher = new EventPublisher($this->transport);
    }

    public function testItPublishesToTheTransportItWasGiven(): void
    {
        $event = new TestEvent();

        $this->transport
            ->expects($this->once())
            ->method("send")
            ->with($event);

        $this->eventPublisher->publish($event);
        $this->assertSame("user-command-queue", $this->eventPublisher->transportName());
    }

    public function testItPublishesABatchToTheTransportItWasGiven(): void
    {
        $event = new TestEvent();

        $this->transport
            ->expects($this->once())
            ->method("sendBatch")
            ->with([$event, $event]);

        $this->eventPublisher->publishAll([$event, $event]);
    }

    public function testItRefusesAnEnvelopeThatIsNotAnEvent(): void
    {
        $this->transport
            ->expects($this->never())
            ->method("sendBatch");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transport "user-command-queue" requires a event to be queued');

        $this->eventPublisher->publishAll([$this->createMock(Envelope::class)]);
    }

    public function testItPublishesNothingForAnEmptyBatch(): void
    {
        $this->transport
            ->expects($this->once())
            ->method("sendBatch")
            ->with([]);

        $this->eventPublisher->publishAll([]);
    }
}
