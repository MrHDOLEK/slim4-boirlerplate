<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Attribute\AsAmqpQueue;
use App\Infrastructure\Events\EventQueue;

#[AsAmqpQueue(name: "test-command-queue", numberOfWorkers: 1)]
class TestEventQueue extends EventQueue
{
}
