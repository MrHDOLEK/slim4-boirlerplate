<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP;

use App\Infrastructure\Messaging\BrokerHealthCheck;

final readonly class AmqpHealthCheck implements BrokerHealthCheck
{
    private const NAME = "RABBITMQ_CONNECTION";

    public function __construct(
        private AMQPStreamConnectionFactory $connectionFactory,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function isHealthy(): bool
    {
        return $this->connectionFactory->get()->isConnected();
    }
}
