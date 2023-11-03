<?php

declare(strict_types=1);

use App\Infrastructure\AMQP\Queue\QueueCompilerPass;
use App\Infrastructure\Console\ConsoleCommandCompilerPass;
use App\Infrastructure\Events\EventHandler\EventHandlerCompilerPass;

return [
    new ConsoleCommandCompilerPass(),
    new QueueCompilerPass(),
    new EventHandlerCompilerPass(),
];
