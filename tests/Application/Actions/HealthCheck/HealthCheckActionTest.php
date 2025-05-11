<?php

declare(strict_types=1);

namespace Tests\Application\Actions\HealthCheck;

use App\Application\Service\HealthCheckService;
use Tests\TestCase;

class HealthCheckActionTest extends TestCase
{
    public function testHealthCheckActionSuccess(): void
    {
        $app = $this->getApp();
        $container = $app->getContainer();

        $healthCheckServiceMock = $this->createMock(HealthCheckService::class);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusList")
            ->willReturn([
                "DB_CONNECTION" => "OK",
                "API_CONNECTION" => "OK",
                "REDIS_CONNECTION" => "OK",
                "RABBITMQ_CONNECTION" => "OK",
            ]);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusCode")
            ->willReturn(200);

        $container->set(HealthCheckService::class, $healthCheckServiceMock);

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $expectedResponseBody = [
            "DB_CONNECTION" => "OK",
            "API_CONNECTION" => "OK",
            "REDIS_CONNECTION" => "OK",
            "RABBITMQ_CONNECTION" => "OK",
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponseBody),
            (string)$response->getBody(),
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHealthCheckActionError(): void
    {
        $app = $this->getApp();
        $container = $app->getContainer();

        $healthCheckServiceMock = $this->createMock(HealthCheckService::class);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusList")
            ->willReturn([
                "DB_CONNECTION" => "ERROR",
                "API_CONNECTION" => "OK",
                "REDIS_CONNECTION" => "ERROR",
                "RABBITMQ_CONNECTION" => "OK",
            ]);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusCode")
            ->willReturn(503);

        $container->set(HealthCheckService::class, $healthCheckServiceMock);

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $expectedResponseBody = [
            "DB_CONNECTION" => "ERROR",
            "API_CONNECTION" => "OK",
            "REDIS_CONNECTION" => "ERROR",
            "RABBITMQ_CONNECTION" => "OK",
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponseBody),
            (string)$response->getBody(),
        );
        $this->assertSame(503, $response->getStatusCode());
    }

    public function testHealthCheckActionPartialError(): void
    {
        $app = $this->getApp();
        $container = $app->getContainer();

        $healthCheckServiceMock = $this->createMock(HealthCheckService::class);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusList")
            ->willReturn([
                "DB_CONNECTION" => "OK",
                "API_CONNECTION" => "OK",
                "REDIS_CONNECTION" => "OK",
                "RABBITMQ_CONNECTION" => "ERROR",
            ]);

        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusCode")
            ->willReturn(503);

        $container->set(HealthCheckService::class, $healthCheckServiceMock);

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $expectedResponseBody = [
            "DB_CONNECTION" => "OK",
            "API_CONNECTION" => "OK",
            "REDIS_CONNECTION" => "OK",
            "RABBITMQ_CONNECTION" => "ERROR",
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponseBody),
            (string)$response->getBody(),
        );
        $this->assertSame(503, $response->getStatusCode());
    }
}
