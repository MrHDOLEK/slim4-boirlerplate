<?php

declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\Environment\Settings;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

/** @var ContainerInterface $container */
$container = ContainerFactory::create();
$app = AppFactory::createFromContainer($container);

// Register routes
(require __DIR__ . "/routes.php")($app);
// Register middleware
(require __DIR__ . "/middleware.php")($app);

// Init
$callableResolver = $app->getCallableResolver();
/** @var Settings $settings */
$settings = $container->get(Settings::class);
/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

$displayErrorDetails = (bool)$settings->get("slim.displayErrorDetails");
$logError = (bool)$settings->get("slim.logErrors");
$logErrorDetails = (bool)$settings->get("slim.logErrorDetails");

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $logger);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

return $app;
