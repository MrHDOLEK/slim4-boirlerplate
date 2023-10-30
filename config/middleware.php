<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use App\Infrastructure\Environment\Settings;
use Slim\App;

return function (App $app): void {
    $settings = $app->getContainer()->get(Settings::class);
    $app->addErrorMiddleware(
        (bool)$settings->get("slim.displayErrorDetails"),
        (bool)$settings->get("slim.logErrors"),
        (bool)$settings->get("slim.logErrorDetails"),
    );
    $app->add(SessionMiddleware::class);
};
