<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Events\DomainEvent;
use App\Infrastructure\Events\EventHandler\EventHandler;

class TestNoCorrespondingEventEventHandler implements EventHandler
{
    public function handle(DomainEvent $event): void
    {
        // TODO: Implement handle() method.
    }
}
