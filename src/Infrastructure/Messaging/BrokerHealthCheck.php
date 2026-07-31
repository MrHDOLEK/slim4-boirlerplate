<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

interface BrokerHealthCheck
{
    public function getName(): string;

    public function isHealthy(): bool;
}
