<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return function (ContainerBuilder $containerBuilder): void {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get("logger");
            $logger = new Logger($loggerSettings["name"]);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings["path"], $loggerSettings["level"]);
            $logger->pushHandler($handler);

            return $logger;
        },
        Twig::class => function (ContainerInterface $container) {
            $settings = $container->get(SettingsInterface::class);
            $twigSettings = $settings->get("twig");

            $options = $twigSettings["options"];
            $options["cache"] = $options["cache_enabled"] ? $options["cache_path"] : false;

            return Twig::create($twigSettings["paths"], $options);
        },
        EntityManager::class => function (ContainerInterface $container) {
            $doctrineSettings = $container->get(SettingsInterface::class)->get("doctrine");

            $cache = $doctrineSettings["dev_mode"] ?
                DoctrineProvider::wrap(new ArrayAdapter()) :
                DoctrineProvider::wrap(new FilesystemAdapter(directory: $doctrineSettings["cache_dir"]));

            $config = Setup::createXMLMetadataConfiguration(
                [(string)$doctrineSettings["metadata_dirs"][0]],
                (bool)$doctrineSettings["dev_mode"],
                (string)$doctrineSettings["proxy_dir"],
                $cache,
            );

            return EntityManager::create($doctrineSettings["connection"], $config);
        },
    ]);
};
