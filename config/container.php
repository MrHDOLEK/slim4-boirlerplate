<?php

declare(strict_types=1);

use App\Domain\Entity\User\UserRepositoryInterface;
use App\Infrastructure\AMQP\AMQPStreamConnectionFactory;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use App\Infrastructure\Persistence\Redis\RedisDoctrineCacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
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
use Predis\Client as RedisClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\Twig;
use Symfony\Component\Cache\Adapter\RedisAdapter;
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
    EntityManager::class => function (Settings $settings, ContainerInterface $container): EntityManager {
        $redisAdapter = $container->get(RedisDoctrineCacheAdapter::class);
        $cachePool = new RedisAdapter($container->get(RedisClient::class));
        $cache = DoctrineProvider::wrap($cachePool);

        $config = Setup::createXMLMetadataConfiguration(
            $settings->get("doctrine.metadata_dirs"),
            $settings->get("doctrine.dev_mode"),
            cache: $cache,
        );

        $config->setResultCache($redisAdapter);
        $config->setMetadataCache($redisAdapter);
        $config->setQueryCache($redisAdapter);
        $config->setAutoGenerateProxyClasses(true);

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
    // AMQP.
    AMQPStreamConnectionFactory::class => function (Settings $settings) {
        $rabbitMqConfig = $settings->get("rabbitmq");

        return new AMQPStreamConnectionFactory(
            $rabbitMqConfig["host"],
            (int)$rabbitMqConfig["port"],
            $rabbitMqConfig["username"],
            $rabbitMqConfig["password"],
            $rabbitMqConfig["vhost"],
        );
    },
    ServerRequestFactoryInterface::class => \DI\get(ServerRequestFactory::class),
    // Redis
    RedisClient::class => function (Settings $settings) {
        $redisConfig = $settings->get("redis");

        $redisConfig = [
            "scheme" => "tcp",
            "host" => $redisConfig["host"] ?? "127.0.0.1",
            "password" => $redisConfig["password"] ?? null,
            "port" => $redisConfig["port"] ?? 6379,
            "database" => $redisConfig["database"] ?? 0,
        ];

        return new RedisClient($redisConfig);
    },
    RedisAdapter::class => fn(ContainerInterface $container) => new RedisAdapter($container->get(RedisClient::class)),
    // Repositories
    UserRepositoryInterface::class => DI\get(UserRepository::class),
];
