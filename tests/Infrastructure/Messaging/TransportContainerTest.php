<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportContainer;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\ContainerTestCase;
use Tests\Support\InMemoryTransport;

class TransportContainerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private TransportContainer $transportContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportContainer = $this->getContainer()->get(TransportContainer::class);
    }

    public function testItRegistersEveryTransportSuccess(): void
    {
        $transports = array_map(fn(Transport $transport) => [
            "transportName" => $transport->getName(),
            "workerName" => $transport->getWorker()->getName(),
            "numberOfConsumers" => $transport->getNumberOfConsumers(),
        ], $this->transportContainer->getTransports());
        ksort($transports);

        $this->assertMatchesJsonSnapshot($transports);
    }

    public function testGetTransportSuccess(): void
    {
        $transport = new InMemoryTransport();
        $this->transportContainer->registerTransport($transport);

        $this->assertEquals(
            $transport,
            $this->transportContainer->getTransport("test-transport"),
        );
    }

    public function testItRefusesTwoTransportsClaimingTheSameName(): void
    {
        $transportContainer = new TransportContainer();
        $transportContainer->registerTransport(new InMemoryTransport());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transport "test-transport" is already registered by');

        $transportContainer->registerTransport(new InMemoryTransport());
    }

    public function testItShouldThrowWhenGettingInvalidTransportName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transport "random-queue" not registered in container');
        $this->transportContainer->getTransport("random-queue");
    }
}
