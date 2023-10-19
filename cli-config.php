<?php

// cli-config.php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\Console\Command\SchemaTool;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use DI\ContainerBuilder;

require __DIR__ . '/vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
    $containerBuilder->enableCompilation(__DIR__ . '/var/cache');
}

// Set up settings
$settings = require __DIR__ . '/app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

return ConsoleRunner::createHelperSet($container->get(EntityManager::class));