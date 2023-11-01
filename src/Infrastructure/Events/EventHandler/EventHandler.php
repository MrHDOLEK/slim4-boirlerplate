<?php

declare(strict_types=1);

namespace App\Infrastructure\Events\EventHandler;

use App\Infrastructure\Events\DomainEvent;

interface EventHandler
{
    public function handle(DomainEvent $event): void;
}
