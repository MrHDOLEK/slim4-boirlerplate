<?php

declare(strict_types=1);

use App\Application\Actions\Docs\OpenApiDocsAction;
use App\Application\Actions\Docs\SwaggerUiAction;
use App\Application\Actions\HealthCheck\HealthCheckAction;
use App\Application\Actions\User\AddUserAction;
use App\Application\Actions\User\GetAllUsersAction;
use App\Application\Actions\User\GetUserByIdAction;
use App\Application\Actions\User\UpdateUserAction;
use App\Application\Validator\UserValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
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
            $group->post("", AddUserAction::class)
                ->add(UserValidator::class)
                ->setName("addUser");
            $group->patch("/{id}", UpdateUserAction::class)
                ->add(UserValidator::class)
                ->setName("updateUserById");
        });

        $group->get("/users", GetAllUsersAction::class)
            ->setName("getAllUsers");
    });
};
