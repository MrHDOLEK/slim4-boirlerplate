<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder): void {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                "slim" => [
                    // Returns a detailed HTML page with error details and
                    // a stack trace. Should be disabled in production.
                    "displayErrorDetails" => (string)getenv("APP_ENV") === "production",

                    // Whether to display errors on the internal PHP log or not.
                    "logErrors" => (bool)getenv("APP_DEBUG") === true,

                    // If true, display full errors with message and stack trace on the PHP log.
                    // If false, display only "Slim Application Error" on the PHP log.
                    // Doesn't do anything when 'logErrors' is false.
                    "logErrorDetails" => (bool)getenv("APP_DEBUG") === true,
                ],
                "doctrine" => [
                    "dev_mode" => true,
                    "cache_dir" => __DIR__ . "/../var/cache/doctrine",
                    "metadata_dirs" => [__DIR__ . "/../src/Infrastructure/Persistence/Doctrine/Mapping/"],
                    "connection" => [
                        "driver" => "pdo_pgsql",
                        "host" => getenv("DB_HOST"),
                        "port" => getenv("DB_PORT"),
                        "dbname" => getenv("DB_NAME"),
                        "user" => getenv("DB_USER"),
                        "password" => getenv("DB_PASSWORD"),
                    ],
                    "migrations" => [
                        "migrations_paths" => [
                            "App\\Infrastructure\\Persistence\\Doctrine\\Migrations" => __DIR__ . "/../src/Infrastructure/Persistence/Doctrine/Migrations",
                        ],
                    ],
                ],
                "twig" => [
                    // Template paths
                    "paths" => [
                        __DIR__ . "/../templates",
                    ],
                    // Twig environment options
                    "options" => [
                        // Should be set to true in production
                        "cache_enabled" => false,
                        "cache_path" => __DIR__ . "/../var/cache/twig",
                    ],
                ],
            ]);
        },
    ]);
};
