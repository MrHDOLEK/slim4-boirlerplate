<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

final readonly class KafkaMessage
{
    /**
     * @param array<mixed> $payload
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $topic,
        public int $partition,
        public int $offset,
        public array $payload,
        public array $headers,
        public ?string $key,
        public ?int $timestamp,
    ) {}

    public function schemaSubject(): ?string
    {
        return $this->headers["schema-subject"] ?? null;
    }

    public function schemaId(): ?int
    {
        return isset($this->headers["schema-id"]) ? (int)$this->headers["schema-id"] : null;
    }

    public function eventType(): ?string
    {
        return $this->headers["event-type"] ?? null;
    }
}
