<?php

declare(strict_types=1);

use App\Application\Actions\Docs\OpenApiDocsAction;
use App\Application\Actions\Docs\SwaggerUiAction;
use App\Application\Actions\HealthCheck\HealthCheckAction;
use App\Application\Actions\User\GetAllUsersAction;
use App\Application\Actions\User\GetUserByIdAction;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Exception\DefaultHtmlErrorRenderer;
use App\Infrastructure\Exception\WhoopsHtmlErrorRenderer;
use App\Infrastructure\Exception\WhoopsJsonErrorRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    /** @var Settings $settings */
    $settings = $app->getContainer()->get(Settings::class);

    $errorMiddleware = $app->addErrorMiddleware(
        (bool)$settings->get("slim.displayErrorDetails"),
        (bool)$settings->get("slim.logErrors"),
        (bool)$settings->get("slim.logErrorDetails"),
    );

    /** @var \Slim\Handlers\ErrorHandler $errorHandler */
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();

    if (!$settings->get("slim.whoops.enabled")) {
        $errorHandler->registerErrorRenderer("text/html", DefaultHtmlErrorRenderer::class);
        $errorHandler->setDefaultErrorRenderer("text/html", DefaultHtmlErrorRenderer::class);

        return;
    }

    $errorHandler->registerErrorRenderer("text/html", WhoopsHtmlErrorRenderer::class);
    $errorHandler->registerErrorRenderer("application/json", WhoopsJsonErrorRenderer::class);
    $errorHandler->setDefaultErrorRenderer("text/html", WhoopsHtmlErrorRenderer::class);

    $routeCollector = $app->getRouteCollector();
    $routeCollector->setDefaultInvocationStrategy(new RequestResponseArgs());

    $app->options("/{routes:.*}", function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get("/", function (Request $request, Response $response) {
        $response->getBody()->write("Hello world!");

        return $response;
    });

    $app->get("/health-check", HealthCheckAction::class);

    $app->get("/docs/v1", SwaggerUiAction::class);
    $app->get("/docs/v1/json", OpenApiDocsAction::class);

    $app->group("/api/v1", function (Group $group): void {
        $group->group("/user", function (Group $group): void {
            $group->get("/{id}", GetUserByIdAction::class)
                ->setName("getUserById");
        });

        $group->get("/users", GetAllUsersAction::class)
            ->setName("getAllUsers");
    });
};
