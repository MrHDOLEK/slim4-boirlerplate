<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

final readonly class TransportMessage
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public mixed $body,
        public array $headers,
        public string $transport,
        public string $source,
        public mixed $handle = null,
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function eventType(): ?string
    {
        return $this->header("event-type");
    }

    public function withBody(mixed $body): self
    {
        return new self($body, $this->headers, $this->transport, $this->source, $this->handle);
    }
}
