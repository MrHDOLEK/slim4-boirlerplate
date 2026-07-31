<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic\DeadLetter;

use App\Infrastructure\Kafka\KafkaMessage;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Serialization\Json;
use DateTimeImmutable;
use Throwable;

final readonly class DeadLetterRecord implements Envelope
{
    /**
     * @param array<string, string> $originalHeaders
     */
    public function __construct(
        public string $originalTopic,
        public int $originalPartition,
        public int $originalOffset,
        public ?string $originalKey,
        public ?int $originalTimestamp,
        public array $originalHeaders,
        public string $payload,
        public string $failureClass,
        public string $failureReason,
        public string $failedAt,
    ) {}

    public static function from(KafkaMessage $message, Throwable $exception, DateTimeImmutable $failedAt): self
    {
        return new self(
            originalTopic: $message->topic,
            originalPartition: $message->partition,
            originalOffset: $message->offset,
            originalKey: $message->key,
            originalTimestamp: $message->timestamp,
            originalHeaders: $message->headers,
            payload: Json::encode($message->payload),
            failureClass: $exception::class,
            failureReason: $exception->getMessage(),
            failedAt: $failedAt->format(DATE_ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            "originalTopic" => $this->originalTopic,
            "originalPartition" => $this->originalPartition,
            "originalOffset" => $this->originalOffset,
            "originalKey" => $this->originalKey,
            "originalTimestamp" => $this->originalTimestamp,
            "originalHeaders" => $this->originalHeaders,
            "payload" => $this->payload,
            "failureClass" => $this->failureClass,
            "failureReason" => $this->failureReason,
            "failedAt" => $this->failedAt,
        ];
    }
}
