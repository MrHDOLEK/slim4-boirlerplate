<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Worker;

use App\Infrastructure\Kafka\KafkaMessage;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use DateInterval;
use DateTimeImmutable;
use Throwable;

interface KafkaWorker
{
    public function getName(): string;

    public function processMessage(KafkaMessage $message): void;

    public function processFailure(KafkaMessage $message, Throwable $exception, KafkaTopic $topic): void;

    public function maxIterationsReached(): bool;

    public function maxLifeTimeReached(): bool;

    public function memoryLimitReached(): bool;

    public function countProcessedMessage(): void;

    public function getMemoryLimitInBytes(): int;

    public function getMaxIterations(): int;

    public function getMaxLifeTime(): DateTimeImmutable;

    public function getMaxLifeTimeInterval(): DateInterval;
}
