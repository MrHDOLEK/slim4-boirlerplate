<?php

declare(strict_types=1);

use App\Infrastructure\Swoole\ServerRequestFactory;
use Swoole\Http\Server as HttpServer;
use Slim\Factory\AppFactory;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Application\Handlers\ErrorHandler;
use App\Infrastructure\Environment\Settings;
use Psr\Log\LoggerInterface;
use Swoole\Runtime;

require_once __DIR__ . '/../vendor/autoload.php';
$container = ContainerFactory::create();
$app = AppFactory::createFromContainer($container);

/** @var Settings $settings */
$settings = $container->get(Settings::class);

Runtime::enableCoroutine(false, SWOOLE_HOOK_ALL);

$server = new HttpServer($settings->get("swoole.server_addr") ?? "localhost", (int)$settings->get("swoole.server_port") ?? 80);
$server->on('workerStart', function () use ($app, $container, $settings) {
    (function ($app) {
        (require __DIR__ . '/../config/middleware.php')($app);
    })($app);

    (function ($app) {
        (require __DIR__ . '/../config/routes.php')($app);
    })($app);
    // Error handling and other settings
    $callableResolver = $app->getCallableResolver();
    /** @var LoggerInterface $logger */
    $logger = $container->get(LoggerInterface::class);
    $displayErrorDetails = (bool)$settings->get("slim.displayErrorDetails");
    $logError = (bool)$settings->get("slim.logErrors");
    $logErrorDetails = (bool)$settings->get("slim.logErrorDetails");
    // Create Error Handler
    $responseFactory = $app->getResponseFactory();
    $errorHandler = new ErrorHandler($callableResolver, $responseFactory, $logger);
    // Add Routing Middleware
    $app->addRoutingMiddleware();
    // Add Error Middleware
    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails, $logger);
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
});

$server->on('request', ServerRequestFactory::createRequestCallback($app));

$server->start();
