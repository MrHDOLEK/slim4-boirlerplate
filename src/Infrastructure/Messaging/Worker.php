<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use DateInterval;
use DateTimeImmutable;
use Throwable;

interface Worker
{
    public function getName(): string;

    public function processMessage(TransportMessage $message): void;

    public function processFailure(TransportMessage $message, Throwable $exception, Transport $transport): void;

    public function countProcessedMessage(): void;

    public function maxIterationsReached(): bool;

    public function maxLifeTimeReached(): bool;

    public function memoryLimitReached(): bool;

    public function getMaxIterations(): int;

    public function getMemoryLimitInBytes(): int;

    public function getMaxLifeTime(): DateTimeImmutable;

    public function getMaxLifeTimeInterval(): DateInterval;
}
