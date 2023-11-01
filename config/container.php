<?php

declare(strict_types=1);

use App\Domain\Entity\User\UserRepositoryInterface;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Dotenv\Dotenv;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Component\Console\Application;
use Twig\Loader\FilesystemLoader;

$appRoot = Settings::getAppRoot();

$dotenv = Dotenv::createImmutable($appRoot);
$dotenv->load();

return [
    // Logger
    LoggerInterface::class => function (ContainerInterface $container) {
        $settings = $container->get(Settings::class);

        $logger = new Logger($settings->get("slim.logger.name"));

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($settings->get("slim.logger.path"), $settings->get("slim.logger.level"));
        $logger->pushHandler($handler);

        return $logger;
    },
    // Clock.
    Clock::class => DI\factory([SystemClock::class, "fromSystemTimezone"]),
    // Twig Environment.
    FilesystemLoader::class => DI\create(FilesystemLoader::class)->constructor($appRoot . "/templates"),
    Twig::class => DI\create(Twig::class)->constructor(DI\get(FilesystemLoader::class)),
    // Doctrine Dbal.
    Connection::class => fn(Settings $settings): Connection => DriverManager::getConnection($settings->get("doctrine.connection")),
    // Doctrine EntityManager.
    EntityManager::class => function (Settings $settings): EntityManager {
        $config = Setup::createXMLMetadataConfiguration(
            $settings->get("doctrine.metadata_dirs"),
            $settings->get("doctrine.dev_mode"),
        );

        return EntityManager::create($settings->get("doctrine.connection"), $config);
    },
    EntityManagerInterface::class => DI\get(EntityManager::class),
    // Console command application.
    Application::class => function (ConsoleCommandContainer $consoleCommandContainer) {
        $application = new Application();

        foreach ($consoleCommandContainer->getCommands() as $command) {
            $application->add($command);
        }

        return $application;
    },
    // Environment.
    Environment::class => fn() => Environment::from($_ENV["ENVIRONMENT"]),
    // Settings.
    Settings::class => DI\factory([Settings::class, "load"]),
    // Repositories
    UserRepositoryInterface::class => DI\get(UserRepository::class),
];