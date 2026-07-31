<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Application\Service\HealthCheckService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Predis\Response\Status;
use Psr\Log\NullLogger;
use Tests\Support\InMemoryBrokerHealthCheck;

class BrokerHealthCheckTest extends TestCase
{
    public function testTheBrokerContributesItsOwnStatusKeyWhicheverBrokerItIs(): void
    {
        $statuses = $this->statusListFor(new InMemoryBrokerHealthCheck("ACME_CONNECTION", true));

        $this->assertArrayHasKey("ACME_CONNECTION", $statuses);
        $this->assertSame(HealthCheckService::STATUS_OK, $statuses["ACME_CONNECTION"]);
    }

    public function testAnUnhealthyBrokerFailsTheOverallCheck(): void
    {
        $statuses = $this->statusListFor(new InMemoryBrokerHealthCheck("ACME_CONNECTION", false));

        $this->assertSame(HealthCheckService::STATUS_ERROR, $statuses["ACME_CONNECTION"]);
    }

    /**
     * @return array<string, string>
     */
    private function statusListFor(InMemoryBrokerHealthCheck $brokerHealthCheck): array
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("isOpen")->willReturn(true);

        $redisClient = $this->createStub(RedisClient::class);
        $redisClient->method("__call")->willReturn(new Status("PONG"));

        return (new HealthCheckService(
            $entityManager,
            $redisClient,
            $brokerHealthCheck,
            new NullLogger(),
        ))->statusList();
    }
}
