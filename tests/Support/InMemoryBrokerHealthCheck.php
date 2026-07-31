<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Messaging\BrokerHealthCheck;

class InMemoryBrokerHealthCheck implements BrokerHealthCheck
{
    public function __construct(
        private readonly string $name,
        private readonly bool $healthy,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isHealthy(): bool
    {
        return $this->healthy;
    }
}
