<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Worker;

use App\Infrastructure\AMQP\Queue\Queue;
use App\Infrastructure\Messaging\Envelope;
use DateInterval;
use DateTimeImmutable;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

interface Worker
{
    public function getName(): string;

    public function processMessage(Envelope $envelope, AMQPMessage $message): void;

    public function processFailure(Envelope $envelope, AMQPMessage $message, Throwable $exception, Queue $queue): void;

    public function maxIterationsReached(): bool;

    public function maxLifeTimeReached(): bool;

    public function getMaxIterations(): int;

    public function getMaxLifeTime(): DateTimeImmutable;

    public function getMaxLifeTimeInterval(): DateInterval;
}
