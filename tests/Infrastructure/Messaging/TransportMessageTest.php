<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\TransportMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

class TransportMessageTest extends TestCase
{
    public function testItExposesHeadersAndTheOpaqueTransportHandle(): void
    {
        $handle = new stdClass();

        $message = new TransportMessage(
            body: ["eventName" => "UserWasCreated"],
            headers: ["event-type" => "App\\Domain\\Service\\User\\DomainEvents\\UserWasCreated", "schema-id" => "7"],
            transport: "first-transport",
            source: "user-events",
            handle: $handle,
        );

        $this->assertSame(["eventName" => "UserWasCreated"], $message->body);
        $this->assertSame("first-transport", $message->transport);
        $this->assertSame("user-events", $message->source);
        $this->assertSame($handle, $message->handle);
        $this->assertSame("7", $message->header("schema-id"));
        $this->assertSame("App\\Domain\\Service\\User\\DomainEvents\\UserWasCreated", $message->eventType());
        $this->assertTrue($message->hasHeader("event-type"));
        $this->assertFalse($message->hasHeader("x-retry-count"));
        $this->assertNull($message->header("x-retry-count"));
    }

    public function testTheHandleIsOptionalAndAccessorsStayNullWithoutHeaders(): void
    {
        $message = new TransportMessage("raw body", [], "second-transport", "user-events-queue");

        $this->assertSame("raw body", $message->body);
        $this->assertNull($message->handle);
        $this->assertNull($message->eventType());
    }

    public function testItCanSwapTheBodyWhileKeepingTheTransportContext(): void
    {
        $handle = new stdClass();
        $message = new TransportMessage("raw", ["event-type" => "Foo"], "second-transport", "queue", $handle);

        $decoded = $message->withBody(["decoded" => true]);

        $this->assertSame(["decoded" => true], $decoded->body);
        $this->assertSame("raw", $message->body);
        $this->assertSame(["event-type" => "Foo"], $decoded->headers);
        $this->assertSame("second-transport", $decoded->transport);
        $this->assertSame("queue", $decoded->source);
        $this->assertSame($handle, $decoded->handle);
    }
}
