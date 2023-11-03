<?php

declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\Environment\Settings;
use DI\Container;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;

/** @var Container $container */
$container = ContainerFactory::create();

return DependencyFactory::fromEntityManager(
    new ConfigurationArray($container->get(Settings::class)->get("doctrine.migrations")),
    new ExistingEntityManager($container->get(EntityManager::class)),
);
