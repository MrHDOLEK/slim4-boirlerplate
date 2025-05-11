<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\AMQP\AMQPStreamConnectionFactory;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class HealthCheckService
{
    public const STATUS_OK = "OK";
    public const STATUS_ERROR = "ERROR";

    private int $statusCode;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisClient $redisClient,
        private AMQPStreamConnectionFactory $rabbitMqClient,
        private LoggerInterface $logger,
    ) {}

    public function statusList(): array
    {
        $statuses = [
            "DB_CONNECTION" => $this->databaseStatus(),
            "API_CONNECTION" => $this->apiStatus(),
            "REDIS_CONNECTION" => $this->redisStatus(),
            "RABBITMQ_CONNECTION" => $this->rabbitMqStatus(),
        ];

        $this->logger->info(
            "Health check statuses",
            array_change_key_case($statuses, CASE_LOWER),
        );

        return $statuses;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function databaseStatus(): string
    {
        if (!$this->entityManager->isOpen()) {
            $this->statusCode = 503;

            return self::STATUS_ERROR;
        }

        $this->statusCode = 200;

        return self::STATUS_OK;
    }

    public function redisStatus(): string
    {
        if (!((string)$this->redisClient->ping()->getPayload() === "PONG")) {
            $this->statusCode = 503;

            return self::STATUS_ERROR;
        }

        $this->statusCode = 200;

        return self::STATUS_OK;
    }

    public function rabbitMqStatus(): string
    {
        if (!$this->rabbitMqClient->get()->isConnected()) {
            $this->statusCode = 503;

            return self::STATUS_ERROR;
        }

        $this->statusCode = 200;

        return self::STATUS_OK;
    }

    public function apiStatus(): string
    {
        return self::STATUS_OK;
    }
}
