<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging\Serializer;

use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Infrastructure\Messaging\Exception\MessageSerializationFailure;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use PHPUnit\Framework\TestCase;

class NativePhpMessageSerializerTest extends TestCase
{
    public function testItEncodesWithTheNativePhpWireFormat(): void
    {
        $event = new UserWasCreated(new User("jdoe", "john", "doe"));

        $this->assertSame(serialize($event), (new NativePhpMessageSerializer())->encode($event));
    }

    public function testItRoundTripsAnEnvelopeIncludingItsNestedPayload(): void
    {
        $serializer = new NativePhpMessageSerializer();
        $event = new UserWasCreated(new User("jdoe", "john", "doe"));

        $decoded = $serializer->decode($serializer->encode($event), []);

        $this->assertInstanceOf(UserWasCreated::class, $decoded);
        $this->assertSame("jdoe", $decoded->user()->username());
        $this->assertSame("John", $decoded->user()->firstName());
        $this->assertSame("Doe", $decoded->user()->lastName());
    }

    public function testItDecodesBodiesWrittenBeforeTheSerializerExisted(): void
    {
        $body = serialize(new UserWasCreated(new User("jdoe", "john", "doe")));

        $this->assertInstanceOf(UserWasCreated::class, (new NativePhpMessageSerializer())->decode($body, []));
    }

    public function testItRejectsAnEnvelopeOutsideTheAllowlist(): void
    {
        $body = serialize(new OutsideTheAllowlist());

        $this->expectException(MessageSerializationFailure::class);
        $this->expectExceptionMessage(OutsideTheAllowlist::class);

        (new NativePhpMessageSerializer())->decode($body, []);
    }

    public function testItRejectsADisallowedClassNestedInsideAnAllowedEnvelope(): void
    {
        $body = serialize(new UserWasCreated(new User("jdoe", "john", "doe")));

        $serializer = new NativePhpMessageSerializer([UserWasCreated::class]);

        $this->expectException(MessageSerializationFailure::class);
        $this->expectExceptionMessage("Refusing to unserialize the message body");

        $serializer->decode($body, []);
    }

    public function testItRejectsADisallowedClassHiddenInAnUntypedProperty(): void
    {
        $body = serialize(new AllowedEnvelopeWithUntypedPayload(new OutsideTheAllowlist()));

        $serializer = new NativePhpMessageSerializer([AllowedEnvelopeWithUntypedPayload::class]);

        $this->expectException(MessageSerializationFailure::class);
        $this->expectExceptionMessage(OutsideTheAllowlist::class);

        $serializer->decode($body, []);
    }

    public function testTheAllowlistIsExtendable(): void
    {
        $serializer = (new NativePhpMessageSerializer())->withAllowedClasses([OutsideTheAllowlist::class]);

        $this->assertContains(OutsideTheAllowlist::class, $serializer->allowedClasses());
        $this->assertInstanceOf(
            OutsideTheAllowlist::class,
            $serializer->decode(serialize(new OutsideTheAllowlist()), []),
        );
    }

    public function testItRejectsABodyThatDoesNotCarryAnEnvelope(): void
    {
        $this->expectException(MessageSerializationFailure::class);

        (new NativePhpMessageSerializer())->decode(serialize(["eventName" => "UserWasCreated"]), []);
    }
}
