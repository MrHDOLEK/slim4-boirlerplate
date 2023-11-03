<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\RunUnitTester;

use App\Infrastructure\Events\DomainEvent;
use App\Infrastructure\Events\EventHandler\EventHandler;

class RunUnitTesterEventHandler implements EventHandler
{
    public function handle(DomainEvent $event): void
    {
        // TODO: Implement handle() method.
    }
}
