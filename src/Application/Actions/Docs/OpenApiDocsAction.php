<?php

declare(strict_types=1);

namespace App\Application\Actions\Docs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OpenApiDocsAction
{
    /**
     * @OA\Get(
     *     path="/docs/v1/json",
     *     tags={"documentation"},
     *     summary="JSON docs",
     *     @OA\Response(response="200", description="success"),
     * )
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $json = json_encode(\OpenApi\Generator::scan(["/var/www/src"]), JSON_PRETTY_PRINT);
        $response->getBody()->write($json);

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(200);
    }
}
