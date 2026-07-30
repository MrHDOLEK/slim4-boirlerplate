<?php

declare(strict_types=1);

use App\Infrastructure\AMQP\Queue\QueueCompilerPass;
use App\Infrastructure\Console\ConsoleCommandCompilerPass;
use App\Infrastructure\Events\EventHandler\EventHandlerCompilerPass;
use App\Infrastructure\Kafka\Topic\KafkaTopicCompilerPass;

return [
    new ConsoleCommandCompilerPass(),
    new QueueCompilerPass(),
    new KafkaTopicCompilerPass(),
    new EventHandlerCompilerPass(),
];
