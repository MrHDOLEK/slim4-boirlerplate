<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Events\DomainEvent;
use App\Infrastructure\Events\EventHandler\EventHandler;

class RunUnitTesterEventHandler implements EventHandler
{
    public function handle(DomainEvent $event): void
    {
    }
}
