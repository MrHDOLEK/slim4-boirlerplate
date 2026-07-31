<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP;

use App\Infrastructure\AMQP\AmqpHealthCheck;
use App\Infrastructure\AMQP\AMQPStreamConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

class AmqpHealthCheckTest extends TestCase
{
    public function testTheAmqpCheckReportsTheConnectionState(): void
    {
        $healthCheck = new AmqpHealthCheck($this->connectionFactorySeeing(true));

        $this->assertSame("RABBITMQ_CONNECTION", $healthCheck->getName());
        $this->assertTrue($healthCheck->isHealthy());
    }

    public function testTheAmqpCheckIsUnhealthyWhenTheConnectionIsDown(): void
    {
        $this->assertFalse((new AmqpHealthCheck($this->connectionFactorySeeing(false)))->isHealthy());
    }

    private function connectionFactorySeeing(bool $connected): AMQPStreamConnectionFactory
    {
        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection->method("isConnected")->willReturn($connected);

        $connectionFactory = $this->createMock(AMQPStreamConnectionFactory::class);
        $connectionFactory->method("get")->willReturn($connection);

        return $connectionFactory;
    }
}
