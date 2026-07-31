<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Exception;

use RuntimeException;

final class ConsumerFailure extends RuntimeException
{
    public static function fatal(int $errorCode, string $reason): self
    {
        return new self(sprintf("Kafka consumer failed: %s (%d)", $reason, $errorCode));
    }

    public static function commitFailed(int $errorCode, string $reason): self
    {
        return new self(sprintf("Could not commit offsets: %s (%d)", $reason, $errorCode));
    }
}
