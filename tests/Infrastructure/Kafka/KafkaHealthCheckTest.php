<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\KafkaHealthCheck;
use PHPUnit\Framework\TestCase;
use RdKafka\Metadata;
use RdKafka\Metadata\Collection;
use RdKafka\Producer;

class KafkaHealthCheckTest extends TestCase
{
    public function testTheKafkaCheckReportsWhetherAnyBrokerAnswered(): void
    {
        $healthCheck = new KafkaHealthCheck($this->producerSeeing(2));

        $this->assertSame("KAFKA_CONNECTION", $healthCheck->getName());
        $this->assertTrue($healthCheck->isHealthy());
    }

    public function testTheKafkaCheckIsUnhealthyWhenNoBrokerAnswered(): void
    {
        $this->assertFalse((new KafkaHealthCheck($this->producerSeeing(0)))->isHealthy());
    }

    private function producerSeeing(int $brokers): Producer
    {
        $collection = $this->createMock(Collection::class);
        $collection->method("count")->willReturn($brokers);

        $metadata = $this->createMock(Metadata::class);
        $metadata->method("getBrokers")->willReturn($collection);

        $producer = $this->createMock(Producer::class);
        $producer->expects($this->once())->method("getMetadata")->with(false, null, 5_000)->willReturn($metadata);

        return $producer;
    }
}
