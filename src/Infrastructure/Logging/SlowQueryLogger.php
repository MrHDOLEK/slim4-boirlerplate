<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Firehed\DbalLogger\DbalLogger;
use Psr\Log\LoggerInterface;
use Throwable;

class SlowQueryLogger implements DbalLogger
{
    private const DEFAULT_SLOW_THRESHOLD_MS = 100.0;
    private const MS_TO_SECONDS = 0.001;
    private const LOG_TYPE = "slow_sql_query";

    private ?float $startTime = null;
    private ?array $types = null;
    private float $slowThreshold;

    public function __construct(
        private LoggerInterface $logger,
        float $slowThresholdMs = self::DEFAULT_SLOW_THRESHOLD_MS,
    ) {
        $this->slowThreshold = $slowThresholdMs * self::MS_TO_SECONDS;
    }

    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        $this->startTime = microtime(true);
        $this->types = $types;
    }

    public function stopQuery(?Throwable $exception = null): void
    {
        if ($this->startTime === null) {
            return;
        }

        $executionTime = microtime(true) - $this->startTime;

        if ($executionTime > $this->slowThreshold) {
            $this->logger->warning("Detected slow SQL query", [
                "log_type" => self::LOG_TYPE,
                "execution_time" => round($executionTime * 1000, 2) . "ms",
                "threshold" => round($this->slowThreshold * 1000, 2) . "ms",
                "types" => $this->types ?: [],
            ]);
        }

        $this->resetQueryData();
    }

    public function connect(): void
    {
    }

    public function disconnect(): void
    {
    }

    private function resetQueryData(): void
    {
        $this->startTime = null;
        $this->types = null;
    }
}
