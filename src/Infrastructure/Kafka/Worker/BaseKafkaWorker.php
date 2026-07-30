<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Worker;

use DateInterval;
use DateTimeImmutable;
use Lcobucci\Clock\Clock;

abstract class BaseKafkaWorker implements KafkaWorker
{
    private const MAX_LIFE_TIME_INTERVAL = "PT1H";
    private const MEMORY_LIMIT_IN_BYTES = 192 * 1024 * 1024;

    private int $counter = 0;
    private DateTimeImmutable $maxLifeTimeDateTime;

    public function __construct(
        private readonly Clock $clock,
    ) {
        $this->maxLifeTimeDateTime = $this->clock->now()->add($this->getMaxLifeTimeInterval());
    }

    public function getMaxIterations(): int
    {
        return 1000;
    }

    public function maxIterationsReached(): bool
    {
        return $this->counter >= $this->getMaxIterations();
    }

    public function countProcessedMessage(): void
    {
        $this->counter++;
    }

    public function getMemoryLimitInBytes(): int
    {
        return self::MEMORY_LIMIT_IN_BYTES;
    }

    public function memoryLimitReached(): bool
    {
        return memory_get_usage(true) >= $this->getMemoryLimitInBytes();
    }

    public function getMaxLifeTime(): DateTimeImmutable
    {
        return $this->maxLifeTimeDateTime;
    }

    public function getMaxLifeTimeInterval(): DateInterval
    {
        return new DateInterval(self::MAX_LIFE_TIME_INTERVAL);
    }

    public function maxLifeTimeReached(): bool
    {
        return $this->clock->now() >= $this->maxLifeTimeDateTime;
    }
}
