<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing\EventListener;

use App\Infrastructure\Eventing\DomainEvent;

interface EventListener
{
    public function notifyThat(DomainEvent $event): void;

    public function isListeningToEvent(DomainEvent $event): bool;
}
