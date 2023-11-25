<?php

declare(strict_types=1);

use Swoole\Http\Server as HttpServer;
use Slim\Factory\AppFactory;
use Slim\Swoole\ServerRequestFactory;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Application\Handlers\HttpErrorHandler;
use App\Infrastructure\Environment\Settings;
use Psr\Log\LoggerInterface;
use Swoole\Runtime;

require_once __DIR__ . '/../vendor/autoload.php';

$container = ContainerFactory::create();
$app = AppFactory::createFromContainer($container);

Runtime::enableCoroutine(false, SWOOLE_HOOK_ALL);

$server = new HttpServer('0.0.0.0', 9501);
$server->set([
    'worker_num' => $_ENV['WORKER_NUM'],
    'pid_file' => __DIR__ . 'server.pid',
]);

$server->on('workerStart', function (HttpServer $server) use ($app, $container) {
    (function ($app) {
        (require __DIR__ . '/../config/middleware.php')($app);
    })($app);

    (function ($app) {
        (require __DIR__ . '/../config/routes.php')($app);
    })($app);

    // Error handling and other settings
    $callableResolver = $app->getCallableResolver();
    /** @var Settings $settings */
    $settings = $container->get(Settings::class);
    /** @var LoggerInterface $logger */
    $logger = $container->get(LoggerInterface::class);

    $displayErrorDetails = (bool)$settings->get("slim.displayErrorDetails");
    $logError = (bool)$settings->get("slim.logErrors");
    $logErrorDetails = (bool)$settings->get("slim.logErrorDetails");

    // Create Error Handler
    $responseFactory = $app->getResponseFactory();
    $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $logger);

    // Add Routing Middleware
    $app->addRoutingMiddleware();

    // Add Error Middleware
    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
});

$server->on('request', ServerRequestFactory::createRequestCallback($app));

$server->start();