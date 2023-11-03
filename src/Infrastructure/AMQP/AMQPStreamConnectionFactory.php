<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use RuntimeException;

class AMQPStreamConnectionFactory
{
    private ?AMQPStreamConnection $AMQPStreamConnection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $vhost,
    ) {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            throw new RuntimeException("Could not find php-amqplib. Install it with composer.");
        }
    }

    public function get(): AMQPStreamConnection
    {
        if ($this->AMQPStreamConnection === null) {
            $this->AMQPStreamConnection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->username,
                $this->password,
                $this->vhost,
            );
        }

        return $this->AMQPStreamConnection;
    }
}
