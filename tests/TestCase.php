<?php

declare(strict_types=1);

namespace Tests;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;
use Slim\Routing\RouteContext;

class TestCase extends PHPUnit_TestCase
{
    use ProphecyTrait;

    protected ?ContainerInterface $container;

    /** @var array<string> */
    protected array $fixtures = [];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $app = $this->getAppInstance();

        $this->container = $app->getContainer();

        if ($this->container === null) {
            throw new Exception();
        }

        $this->purgeDatabase();

        $this->loadDoctrineFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);
        $em->getConnection()->close();
    }

    public function loadDoctrineFixtures(): void
    {
        $fixtures = [];

        foreach ($this->fixtures as $fixtureFile) {
            $fixtures[] = new $fixtureFile();
        }

        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);
        $executor = new ORMExecutor($em);
        $executor->execute($fixtures, true);
    }

    /**
     * @throws Exception
     */
    protected function getAppInstance(): App
    {
        $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . "/../");
        $dotenv->safeLoad();

        // Instantiate PHP-DI ContainerBuilder
        $containerBuilder = new ContainerBuilder();

        // Container intentionally not compiled for tests.

        // Set up settings
        (require __DIR__ . "/../app/settings.php")($containerBuilder);

        // Set up dependencies
        (require __DIR__ . "/../app/dependencies.php")($containerBuilder);

        // Set up repositories
        (require __DIR__ . "/../app/repositories.php")($containerBuilder);

        // Build PHP-DI Container instance
        $container = $containerBuilder->build();

        // Instantiate the app
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Register middleware
        (require __DIR__ . "/../app/middleware.php")($app);

        // Register routes
        (require __DIR__ . "/../app/routes.php")($app);

        return $app;
    }

    protected function createRequest(
        string $method,
        string $path,
        array $headers = ["HTTP_ACCEPT" => "application/json"],
        array $cookies = [],
        array $serverParams = [],
        string $body = "",
        bool $withRoutingResults = false,
    ): Request {
        $uri = new Uri("", "", 80, $path);
        $handle = fopen("php://temp", "w+");

        if (!empty($body)) {
            $stream = (new StreamFactory())->createStream($body);
        } else {
            $stream = (new StreamFactory())->createStreamFromResource($handle);
        }
        $headers = new Headers($headers);
        $request = new SlimRequest($method, $uri, $headers, $cookies, $serverParams, $stream);

        if ($withRoutingResults === true) {
            $request = $request->withAttribute(
                RouteContext::ROUTING_RESULTS,
                $this->getAppInstance()->getRouteResolver()->computeRoutingResults(
                    $path,
                    $method,
                ),
            );
        }

        return $request;
    }

    protected function purgeDatabase(): void
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();
    }

    protected function addFixtures(string $fixture): void
    {
        $this->fixtures[] = $fixture;
    }
}
