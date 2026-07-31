<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Messaging\BrokerHealthCheck;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class HealthCheckService
{
    public const STATUS_OK = "OK";
    public const STATUS_ERROR = "ERROR";

    private ?array $cachedStatuses = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisClient $redisClient,
        private BrokerHealthCheck $brokerHealthCheck,
        private LoggerInterface $logger,
    ) {}

    public function statusList(): array
    {
        if ($this->cachedStatuses === null) {
            $this->cachedStatuses = [
                "DB_CONNECTION" => $this->databaseStatus(),
                "API_CONNECTION" => $this->apiStatus(),
                "REDIS_CONNECTION" => $this->redisStatus(),
                $this->brokerHealthCheck->getName() => $this->brokerStatus(),
            ];

            $this->logger->info(
                "Health check statuses",
                array_change_key_case($this->cachedStatuses, CASE_LOWER),
            );
        }

        return $this->cachedStatuses;
    }

    public function statusCode(): int
    {
        $statuses = $this->statusList();

        return $this->calculateStatusCode($statuses);
    }

    private function calculateStatusCode(array $statuses): int
    {
        if (array_any($statuses, fn($status) => $status === self::STATUS_ERROR)) {
            return StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE;
        }

        return StatusCodeInterface::STATUS_OK;
    }

    private function databaseStatus(): string
    {
        try {
            if (!$this->entityManager->isOpen()) {
                return self::STATUS_ERROR;
            }

            return self::STATUS_OK;
        } catch (Exception $exception) {
            $this->logger->error(
                "Database connection check failed with exception",
                [
                    "exception" => $exception->getMessage(),
                    "status" => self::STATUS_ERROR,
                    "component" => "database",
                    "trace" => $exception->getTraceAsString(),
                ],
            );

            return self::STATUS_ERROR;
        }
    }

    private function redisStatus(): string
    {
        try {
            if (!((string)$this->redisClient->ping()->getPayload() === "PONG")) {
                return self::STATUS_ERROR;
            }

            return self::STATUS_OK;
        } catch (Exception $exception) {
            $this->logger->error(
                "Redis connection check failed with exception",
                [
                    "exception" => $exception->getMessage(),
                    "status" => self::STATUS_ERROR,
                    "component" => "redis",
                    "trace" => $exception->getTraceAsString(),
                ],
            );

            return self::STATUS_ERROR;
        }
    }

    private function brokerStatus(): string
    {
        try {
            if (!$this->brokerHealthCheck->isHealthy()) {
                return self::STATUS_ERROR;
            }

            return self::STATUS_OK;
        } catch (Exception $exception) {
            $this->logger->error(
                "Message broker connection check failed with exception",
                [
                    "exception" => $exception->getMessage(),
                    "status" => self::STATUS_ERROR,
                    "component" => strtolower($this->brokerHealthCheck->getName()),
                    "trace" => $exception->getTraceAsString(),
                ],
            );

            return self::STATUS_ERROR;
        }
    }

    private function apiStatus(): string
    {
        return self::STATUS_OK;
    }
}
