<?php

declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\Environment\Settings;
use DI\Container;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

/** @var Container $container */
$container = ContainerFactory::create();

$factory = DependencyFactory::fromEntityManager(
    new ConfigurationArray($container->get(Settings::class)->get("doctrine.migrations")),
    new ExistingEntityManager($container->get(EntityManager::class)),
);

ConsoleRunner::run(
    new SingleManagerProvider($container->get(EntityManagerInterface::class)),
    [
        new ExecuteCommand($factory),
        new GenerateCommand($factory),
        new LatestCommand($factory),
        new MigrateCommand($factory),
        new DiffCommand($factory),
        new UpToDateCommand($factory),
        new StatusCommand($factory),
        new VersionCommand($factory),
        new DumpSchemaCommand($factory),
    ],
);

return $factory;
