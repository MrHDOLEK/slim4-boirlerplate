<?php

declare(strict_types=1);

namespace Tests\Application\Actions\HealthCheck;

use App\Application\Service\HealthCheckService;
use DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class HealthCheckActionTest extends TestCase
{
    public function testHealthCheckActionSuccess(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        /** @var HealthCheckService&MockObject $healthCheckServiceMock */
        $healthCheckServiceMock = $this->createMock(HealthCheckService::class);
        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusCode")
            ->willReturn(200);
        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusList")
            ->willReturn([
                "API_CONNECTION" => "OK",
            ]);

        $container->set(HealthCheckService::class, $healthCheckServiceMock);

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $this->assertJsonStringEqualsJsonString(
            json_encode(["API_CONNECTION" => "OK"]),
            (string)$response->getBody(),
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHealthCheckActionError(): void
    {
        $app = $this->getApp();
        /** @var Container $container */
        $container = $app->getContainer();

        /** @var HealthCheckService&MockObject $healthCheckServiceMock */
        $healthCheckServiceMock = $this->createMock(HealthCheckService::class);
        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusCode")
            ->willReturn(503);
        $healthCheckServiceMock
            ->expects($this->once())
            ->method("statusList")
            ->willReturn([
                "API_CONNECTION" => "OK",
            ]);

        $container->set(HealthCheckService::class, $healthCheckServiceMock);

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $this->assertJsonStringEqualsJsonString(
            json_encode(["API_CONNECTION" => "OK"]),
            (string)$response->getBody(),
        );
        $this->assertSame(503, $response->getStatusCode());
    }
}
