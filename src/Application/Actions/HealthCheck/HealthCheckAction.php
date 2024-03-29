<?php

declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use App\Application\Service\HealthCheckService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HealthCheckAction
{
    public function __construct(
        protected HealthCheckService $healthCheck,
    ) {}

    #[OA\Get(
        path: "/health-check",
        summary: "Lists API status",
        tags: ["status"],
        responses: [
            new OA\Response(
                response: "200",
                description: "success",
            ),
            new OA\Response(
                response: "503",
                description: "some services are not responding",
            ),
        ],
    )]
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode($this->healthCheck->statusList(), JSON_PRETTY_PRINT));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus($this->healthCheck->statusCode());
    }
}
