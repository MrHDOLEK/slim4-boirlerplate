<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class SlowQueryLogger extends AbstractLogger
{
    public const DEFAULT_SLOW_THRESHOLD_MS = 100.0;

    private float $slowThreshold;

    public function __construct(
        private LoggerInterface $logger,
        float $slowThresholdMs = self::DEFAULT_SLOW_THRESHOLD_MS,
    ) {
        $this->slowThreshold = $slowThresholdMs * 0.001;
    }

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $exec = $context["executionMS"] ?? null;

        if (!is_float($exec) || $exec <= $this->slowThreshold) {
            return;
        }

        $this->logger->warning("Detected slow SQL query", [
            "log_type" => "slow_sql_query",
            "execution_time" => round($exec * 1000, 2) . "ms",
            "threshold" => round($this->slowThreshold * 1000, 2) . "ms",
            "sql" => $message,
            "params" => $context["params"] ?? [],
            "types" => $context["types"] ?? [],
        ]);
    }
}
