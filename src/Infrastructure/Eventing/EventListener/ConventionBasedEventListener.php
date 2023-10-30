<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing\EventListener;

use App\Infrastructure\Attribute\AsEventListener;
use App\Infrastructure\Eventing\DomainEvent;

abstract class ConventionBasedEventListener implements EventListener
{
    private string $eventProcessingMethodPrefix;

    /** @var array<string> */
    private array $eventsThatWeAreListeningTo;

    public function __construct()
    {
        $this->eventProcessingMethodPrefix = $this->resolveEventProcessingMethodPrefix();
        $this->eventsThatWeAreListeningTo = $this->resolveEventsThatWeAreListeningTo();
    }

    public function notifyThat(DomainEvent $event): void
    {
        $methodName = $this->eventProcessingMethodPrefix . $event->getShortClassName();

        if (!\method_exists($this, $methodName)) {
            return;
        }
        $this->{$methodName}($event);
    }

    public function isListeningToEvent(DomainEvent $event): bool
    {
        return in_array($event::class, $this->eventsThatWeAreListeningTo, true);
    }

    /**
     * @return array<string>
     */
    private function resolveEventsThatWeAreListeningTo(): array
    {
        $interestedIn = [];
        $methods = (new \ReflectionClass($this))->getMethods();

        foreach ($methods as $method) {
            if (!str_starts_with($method->getName(), $this->eventProcessingMethodPrefix)) {
                continue;
            }

            $methodParams = $method->getParameters();
            $eventFullFqcn = $methodParams[0]->getType()->getName();
            $reflection = new \ReflectionClass($eventFullFqcn);

            // Method name needs to equal with "prefixEventName()"
            if ($method->getName() !== $this->eventProcessingMethodPrefix . $reflection->getShortName()) {
                continue;
            }

            // Guard that there is only one param to the method.
            if (count($methodParams) !== 1) {
                continue;
            }

            // Guard that the one param is of type "DomainEvent".
            if (!$reflection->newInstanceWithoutConstructor() instanceof DomainEvent) {
                continue;
            }

            $interestedIn[] = $eventFullFqcn;
        }

        return $interestedIn;
    }

    private function resolveEventProcessingMethodPrefix(): string
    {
        if (!$attributes = (new \ReflectionClass($this))->getAttributes(AsEventListener::class)) {
            throw new \RuntimeException(sprintf("Event listener %s not tagged with attribute", static::class));
        }

        /** @var \App\Infrastructure\Eventing\EventListener\EventListenerType $type */
        $type = $attributes[0]->newInstance()->getType();

        return $type->getEventProcessingMethodPrefix();
    }
}
