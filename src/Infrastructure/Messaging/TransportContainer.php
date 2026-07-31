<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use RuntimeException;

class TransportContainer
{
    /** @var array<string, Transport> */
    private array $transports = [];

    public function registerTransport(Transport $transport): void
    {
        $name = $transport->getName();

        if (array_key_exists($name, $this->transports)) {
            throw new RuntimeException(sprintf(
                'Transport "%s" is already registered by "%s", "%s" cannot claim the same name',
                $name,
                $this->transports[$name]::class,
                $transport::class,
            ));
        }

        $this->transports[$name] = $transport;
    }

    public function getTransport(string $name): Transport
    {
        if (!array_key_exists($name, $this->transports)) {
            throw new RuntimeException(sprintf('Transport "%s" not registered in container', $name));
        }

        return $this->transports[$name];
    }

    /**
     * @return array<string, Transport>
     */
    public function getTransports(): array
    {
        return $this->transports;
    }
}
