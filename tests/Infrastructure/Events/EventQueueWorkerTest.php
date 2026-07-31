<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Events\EventBus;
use App\Infrastructure\Events\EventQueueWorker;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\PausedClock;

class EventQueueWorkerTest extends TestCase
{
    private EventQueueWorker $commandQueueWorker;
    private MockObject $commandBus;
    private Clock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(EventBus::class);
        $this->clock = PausedClock::on(new DateTimeImmutable("2022-07-01"));

        $this->commandQueueWorker = new EventQueueWorker(
            $this->commandBus,
            $this->clock,
        );
    }

    public function testProcessMessageSuccess(): void
    {
        $command = new TestEvent();

        $this->commandBus
            ->expects($this->once())
            ->method("dispatch")
            ->with($command);

        $this->commandQueueWorker->processMessage($this->message($command));
        $this->assertEquals("event-queue-worker", $this->commandQueueWorker->getName());
    }

    public function testProcessFailureEnrichesTheEventWithTheFailure(): void
    {
        $command = new TestEvent();

        $this->commandQueueWorker->processFailure(
            $this->message($command),
            new RuntimeException("A grave error"),
            $this->createMock(Transport::class),
        );

        $this->assertSame("A grave error", $command->jsonSerialize()["payload"]["metadata"]["exceptionMessage"]);
    }

    public function testItRefusesABodyThatIsNotAnEvent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("can only handle events, string given");

        $this->commandQueueWorker->processMessage(
            new TransportMessage("not an event", [], "test-transport", "test-command-queue"),
        );
    }

    private function message(TestEvent $event): TransportMessage
    {
        return new TransportMessage($event, [], "test-transport", "test-command-queue");
    }
}
