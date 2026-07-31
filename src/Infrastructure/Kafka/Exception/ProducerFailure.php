<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Exception;

use RuntimeException;

final class ProducerFailure extends RuntimeException
{
    public static function flushFailed(string $topic, int $errorCode): self
    {
        return new self(sprintf(
            'Could not flush the producer for topic "%s": %s (%d). Messages still in the queue were not delivered.',
            $topic,
            rd_kafka_err2str($errorCode),
            $errorCode,
        ));
    }
}
