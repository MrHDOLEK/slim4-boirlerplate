<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\KafkaMessage;
use PHPUnit\Framework\TestCase;

class KafkaMessageTest extends TestCase
{
    public function testItExposesSchemaMetadataFromHeaders(): void
    {
        $message = new KafkaMessage(
            topic: "user-events",
            partition: 0,
            offset: 12,
            payload: ["eventName" => "UserWasCreated"],
            headers: [
                "schema-subject" => "user-events-value",
                "schema-id" => "7",
                "event-type" => "App\\Domain\\Service\\User\\DomainEvents\\UserWasCreated",
            ],
            key: "jdoe",
            timestamp: 1_700_000_000,
        );

        $this->assertSame("user-events-value", $message->schemaSubject());
        $this->assertSame(7, $message->schemaId());
        $this->assertSame("App\\Domain\\Service\\User\\DomainEvents\\UserWasCreated", $message->eventType());
    }

    public function testSchemaAccessorsAreNullWhenHeadersAreAbsent(): void
    {
        $message = new KafkaMessage("t", 0, 0, [], [], null, null);

        $this->assertNull($message->schemaSubject());
        $this->assertNull($message->schemaId());
        $this->assertNull($message->eventType());
    }
}
