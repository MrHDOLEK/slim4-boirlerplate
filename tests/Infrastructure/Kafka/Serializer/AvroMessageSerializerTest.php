<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka\Serializer;

use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Infrastructure\Kafka\Exception\SchemaRegistryFailure;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Serializer\EncodedRecord;
use App\Infrastructure\Messaging\Exception\MessageSerializationFailure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AvroMessageSerializerTest extends TestCase
{
    private const CONFLUENT_PAYLOAD = "\x00\x00\x00\x00\x07encoded-avro";

    private AvroSerializer&MockObject $avroSerializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->avroSerializer = $this->createMock(AvroSerializer::class);
    }

    public function testItRebuildsTheEnvelopeFromTheEventTypeHeader(): void
    {
        $this->avroSerializer
            ->method("decode")
            ->willReturn([
                "eventName" => "App.Domain.Service.User.DomainEvents.UserWasCreated",
                "payload" => [
                    "user" => ["username" => "jdoe", "firstName" => "John", "lastName" => "Doe"],
                ],
            ]);

        $envelope = $this->serializer()->decode(self::CONFLUENT_PAYLOAD, [
            "event-type" => UserWasCreated::class,
            "schema-id" => "7",
        ]);

        $this->assertInstanceOf(UserWasCreated::class, $envelope);
        $this->assertSame("jdoe", $envelope->user()->username());
        $this->assertSame("John", $envelope->user()->firstName());
        $this->assertSame("Doe", $envelope->user()->lastName());
    }

    public function testItResolvesTheWriterSchemaFromThePayloadAndNeverFromTheHeader(): void
    {
        $this->avroSerializer
            ->expects($this->once())
            ->method("decode")
            ->with(self::CONFLUENT_PAYLOAD)
            ->willReturn(["eventName" => "UserWasCreated", "payload" => ["user" => [
                "username" => "jdoe",
                "firstName" => "John",
                "lastName" => "Doe",
            ]]]);

        $this->avroSerializer
            ->expects($this->never())
            ->method("schemaIdOf");

        $this->serializer()->decode(self::CONFLUENT_PAYLOAD, [
            "event-type" => UserWasCreated::class,
            "schema-id" => "9999",
        ]);
    }

    public function testItRefusesToDecodeWithoutAUsableEventTypeHeader(): void
    {
        $this->expectException(MessageSerializationFailure::class);
        $this->expectExceptionMessage("event-type");

        $this->serializer()->decode(self::CONFLUENT_PAYLOAD, ["schema-id" => "7"]);
    }

    public function testItRefusesToDecodeWhenTheEventTypeIsNotAnEnvelope(): void
    {
        $this->expectException(MessageSerializationFailure::class);

        $this->serializer()->decode(self::CONFLUENT_PAYLOAD, ["event-type" => self::class]);
    }

    public function testItEncodesThroughTheAvroSerializerForTheBoundSubject(): void
    {
        $event = $this->event();

        $this->avroSerializer
            ->expects($this->once())
            ->method("encode")
            ->with("user-events-value", $event->jsonSerialize())
            ->willReturn(new EncodedRecord(self::CONFLUENT_PAYLOAD, "user-events-value", 7, 1));

        $this->assertSame(
            self::CONFLUENT_PAYLOAD,
            $this->serializer()->forSubject("user-events-value")->encode($event),
        );
    }

    public function testItRefusesToEncodeWhenNoSubjectIsBound(): void
    {
        $this->expectException(SchemaRegistryFailure::class);
        $this->expectExceptionMessage(UserWasCreated::class);

        $this->serializer()->encode($this->event());
    }

    private function serializer(): AvroMessageSerializer
    {
        return new AvroMessageSerializer($this->avroSerializer, $this->denormalizer());
    }

    private function event(): UserWasCreated
    {
        return new UserWasCreated(new User("jdoe", "john", "doe"));
    }

    private function denormalizer(): DenormalizerInterface
    {
        return new Serializer([
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, null, new PropertyAccessor(), new ReflectionExtractor()),
        ], []);
    }
}
