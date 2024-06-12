<?php

declare(strict_types=1);

namespace App\Infrastructure\Queues;

use App\Infrastructure\Attribute\AsAmqpQueue;
use App\Infrastructure\Events\EventQueue;

#[AsAmqpQueue(name: "user-command-queue", numberOfWorkers: 1)]
class UserEventQueue extends EventQueue
{
}
