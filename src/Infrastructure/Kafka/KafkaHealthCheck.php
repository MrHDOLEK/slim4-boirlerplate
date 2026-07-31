<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

use App\Infrastructure\Messaging\BrokerHealthCheck;
use RdKafka\Producer;

final readonly class KafkaHealthCheck implements BrokerHealthCheck
{
    private const NAME = "KAFKA_CONNECTION";
    private const METADATA_TIMEOUT_MS = 5_000;

    public function __construct(
        private Producer $producer,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function isHealthy(): bool
    {
        return $this->producer->getMetadata(false, null, self::METADATA_TIMEOUT_MS)->getBrokers()->count() > 0;
    }
}
