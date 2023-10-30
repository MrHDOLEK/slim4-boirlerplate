<?php

declare(strict_types=1);

use App\Infrastructure\AMQP\Queue\QueueCompilerPass;
use App\Infrastructure\Console\ConsoleCommandCompilerPass;
use App\Infrastructure\Eventing\EventListener\EventListenerCompilerPass;

return [
    new ConsoleCommandCompilerPass(),
    new EventListenerCompilerPass(),
    new QueueCompilerPass(),
];
