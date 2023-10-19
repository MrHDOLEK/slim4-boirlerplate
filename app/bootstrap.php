<?php

declare(strict_types=1);

use DI\ContainerBuilder;

require __DIR__ . "/../vendor/autoload.php";

if (file_exists(__DIR__ . "/../.env")) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . "/../");
    $dotenv->safeLoad();
    $dotenv->required([
        "APP_ENV",
        "APP_DEBUG",
        "DB_HOST",
        "DB_NAME",
        "DB_USER",
        "DB_PASSWORD",
    ]);
}

$containerBuilder = new ContainerBuilder();

if (getenv("APP_ENV") === "prod") {
    $containerBuilder->enableCompilation(__DIR__ . "/../var/cache");
}

// Set up settings
(require __DIR__ . "/../app/settings.php")($containerBuilder);

// Set up dependencies
(require __DIR__ . "/../app/dependencies.php")($containerBuilder);

// Set up repositories
(require __DIR__ . "/../app/repositories.php")($containerBuilder);

// Build PHP-DI Container instance
return $containerBuilder->build();
