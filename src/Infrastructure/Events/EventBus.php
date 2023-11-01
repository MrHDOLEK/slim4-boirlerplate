<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Infrastructure\Events\EventHandler\CanNotRegisterEventHandler;
use App\Infrastructure\Events\EventHandler\EventHandler;

class EventBus
{
    private const EVENT_HANDLER_SUFFIX = "EventHandler";

    /** @var array<EventHandler> */
    private array $eventHandlers = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->getHandlerForEvent($event)->handle($event);
    }

    public function subscribeEventHandler(EventHandler $eventHandler): void
    {
        $this->guardThatFqcnEndsInEventHandler($eventHandler::class);
        $this->guardThatThereIsACorrespondingEvent($eventHandler);

        $eventFqcn = str_replace(self::EVENT_HANDLER_SUFFIX, "", $eventHandler::class);
        $this->eventHandlers[$eventFqcn] = $eventHandler;
    }

    /**
     * @return array<EventHandler>
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    private function getHandlerForEvent(DomainEvent $event): EventHandler
    {
        return $this->eventHandlers[$event::class] ??
            throw new \RuntimeException(sprintf('EventHandler for event "%s" not subscribed to this bus', $event::class));
    }

    private function guardThatFqcnEndsInEventHandler(string $fqcn): void
    {
        if (str_ends_with($fqcn, self::EVENT_HANDLER_SUFFIX)) {
            return;
        }

        throw new CanNotRegisterEventHandler(sprintf('Fqcn "%s" does not end with "EventHandler"', $fqcn));
    }

    private function guardThatThereIsACorrespondingEvent(EventHandler $eventHandler): void
    {
        $eventFqcn = str_replace(self::EVENT_HANDLER_SUFFIX, "", $eventHandler::class);

        if (!class_exists($eventFqcn)) {
            throw new CanNotRegisterEventHandler(sprintf('No corresponding event for eventHandler "%s" found', $eventHandler::class));
        }

        if (str_ends_with($eventFqcn, "Event")) {
            throw new CanNotRegisterEventHandler('Event name cannot end with "event"');
        }
    }
}
