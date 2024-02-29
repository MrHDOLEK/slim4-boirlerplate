<?php

declare(strict_types=1);

namespace Tests;

use DI\Container;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;
use Slim\Routing\RouteContext;

class TestCase extends PHPUnit_TestCase
{
    use ProphecyTrait;

    protected array $fixtures = [];
    private App $app;

    /** @var Container */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = require dirname(__DIR__) . "/config/bootstrap.php";
        $this->container = $this->app->getContainer();
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

    public function getApp(): App
    {
        return $this->app;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getOpenApiPatch(): string
    {
        return dirname(__DIR__) . "/resources/docs/openapi.json";
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
                $this->getApp()->getRouteResolver()->computeRoutingResults(
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
