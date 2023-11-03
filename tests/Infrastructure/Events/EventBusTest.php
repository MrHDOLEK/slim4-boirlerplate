<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Events\EventBus;
use App\Infrastructure\Serialization\Json;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\Infrastructure\AMQP\RunUnitTester\RunUnitTesterEventHandler;
use Tests\Infrastructure\Events\InvalidTestEvent\InvalidTestEventEventHandler;
use Tests\TestCase;

class EventBusTest extends TestCase
{
    use MatchesSnapshots;

    private EventBus $eventBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->getContainer()->get(EventBus::class);
    }

    public function testItRegistersAllEventSuccess(): void
    {
        $events = array_keys($this->eventBus->getEventHandlers());
        sort($events);
        $this->assertMatchesJsonSnapshot(Json::encode($events));
    }

    public function testItRegistersEventSuccess(): void
    {
        $eventHandler = new RunUnitTesterEventHandler();
        $this->eventBus->subscribeEventHandler($eventHandler);
        $this->assertContains($eventHandler, $this->eventBus->getEventHandlers());
    }

    public function testGetItShouldThrowOnInvalidEventHandler(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('EventHandler for event "Tests\Infrastructure\Events\TestEvent" not subscribed to this bus');

        $this->eventBus->dispatch(new TestEvent());
    }

    public function testSubscribeEventHandlerItShouldThrowWhenNoCorrespondingEvent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No corresponding event for eventHandler "Tests\Infrastructure\Events\TestNoCorrespondingEventEventHandler" found');

        $this->eventBus->subscribeEventHandler(new TestNoCorrespondingEventEventHandler());
    }

    public function testSubscribeEventHandlerItShouldThrowWhenInvalidEventHandlerName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fqcn "Tests\Infrastructure\Events\TestInvalidEventHandlerName" does not end with "EventHandler"');

        $this->eventBus->subscribeEventHandler(new TestInvalidEventHandlerName());
    }

    public function testSubscribeEventHandlerItShouldThrowWhenInvalidEventName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No corresponding event for eventHandler "Tests\Infrastructure\Events\InvalidTestEvent\InvalidTestEventEventHandler');

        $this->eventBus->subscribeEventHandler(new InvalidTestEventEventHandler());
    }
}
