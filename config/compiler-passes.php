<?php

declare(strict_types=1);

use App\Infrastructure\AMQP\Attribute\AsAmqpQueue;
use App\Infrastructure\Console\ConsoleCommandCompilerPass;
use App\Infrastructure\Events\EventHandler\EventHandlerCompilerPass;
use App\Infrastructure\Kafka\Attribute\AsKafkaTopic;
use App\Infrastructure\Messaging\TransportCompilerPass;

return [
    new ConsoleCommandCompilerPass(),
    new TransportCompilerPass([
        AsAmqpQueue::class,
        AsKafkaTopic::class,
    ]),
    new EventHandlerCompilerPass(),
];
