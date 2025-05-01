<?php

declare(strict_types=1);

namespace App\Application\Actions\Docs;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Symfony\Component\Yaml\Yaml;

#[OA\Info(version: "0.1", title: "API")]
#[OA\Server(url: "/", description: "Local server")]
final class SwaggerUiAction
{
    public function __construct(
        private readonly Twig $twig,
    ) {}

    #[OA\Get(
        path: "/docs/v1",
        summary: "Swagger UI",
        tags: ["documentation"],
        responses: [new OA\Response(
            response: "200",
            description: "success",
        )],
    )]
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = [
            "spec" => json_encode(Yaml::parseFile(__DIR__ . "/../../../../resources/docs/openapi.yaml")),
        ];

        return $this->twig->render($response, "docs/swagger.twig", $viewData);
    }
}
