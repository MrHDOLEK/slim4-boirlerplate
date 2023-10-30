<?php

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\Environment\Settings;
use DI\Bridge\Slim\Bridge;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

$container = ContainerFactory::create();
$app = Bridge::create($container);

// Register routes
(require __DIR__.'/routes.php')($app);
// Register middleware
(require __DIR__.'/middleware.php')($app);

return $app;
