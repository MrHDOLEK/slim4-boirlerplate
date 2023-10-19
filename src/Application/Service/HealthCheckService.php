<?php

declare(strict_types=1);

namespace App\Application\Service;

class HealthCheckService
{
    public const STATUS_OK = "OK";
    public const STATUS_ERROR = "ERROR";

    private int $statusCode = 200;

    public function statusList(): array
    {
        return [
            "API_CONNECTION" => $this->apiStatus(),
        ];
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function apiStatus(): string
    {
        return self::STATUS_OK;
    }
}
