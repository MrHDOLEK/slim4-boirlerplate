<?php

declare(strict_types=1);

namespace Tests\Application\Actions\HealthCheck;

use App\Application\Service\HealthCheckService;
use DI\Container;
use Tests\TestCase;

class HealthCheckActionTest extends TestCase
{
    public function testHealthCheckActionSuccess(): void
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $healthCheckServiceProphecy = $this->prophesize(HealthCheckService::class);
        $healthCheckServiceProphecy
            ->statusCode()
            ->willReturn(200)
            ->shouldBeCalledOnce();
        $healthCheckServiceProphecy
            ->statusList()
            ->willReturn([
                "API_CONNECTION" => "OK",
            ])
            ->shouldBeCalledOnce();

        $container->set(HealthCheckService::class, $healthCheckServiceProphecy->reveal());

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "API_CONNECTION" => "OK",
            ]),
            (string)$response->getBody(),
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHealthCheckActionError(): void
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $healthCheckServiceProphecy = $this->prophesize(HealthCheckService::class);
        $healthCheckServiceProphecy
            ->statusCode()
            ->willReturn(503)
            ->shouldBeCalledOnce();
        $healthCheckServiceProphecy
            ->statusList()
            ->willReturn([
                "API_CONNECTION" => "OK",
            ])
            ->shouldBeCalledOnce();

        $container->set(HealthCheckService::class, $healthCheckServiceProphecy->reveal());

        $request = $this->createRequest("GET", "/health-check");
        $response = $app->handle($request);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                "API_CONNECTION" => "OK",
            ]),
            (string)$response->getBody(),
        );
        $this->assertEquals(503, $response->getStatusCode());
    }
}
