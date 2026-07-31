<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopic;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopicFactory;
use App\Infrastructure\Messaging\Transport;
use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Tests\PausedClock;

class DeadLetterTopicTest extends TestCase
{
    public function testItDerivesItsNameFromTheSourceTopic(): void
    {
        $this->assertSame("user-events-dead-letter", $this->deadLetterTopic()->getName());
    }

    public function testItUsesTheSharedDeadLetterSchemaSubject(): void
    {
        $this->assertSame("dead-letter-value", $this->deadLetterTopic()->getSchemaSubject());
    }

    public function testItIsNotConsumable(): void
    {
        $this->assertSame(0, $this->deadLetterTopic()->getNumberOfConsumers());
    }

    public function testItHasNoWorker(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Dead letter topics do not have workers");

        $this->deadLetterTopic()->getWorker();
    }

    public function testTheFactoryBuildsATopicForTheGivenSource(): void
    {
        $producer = $this->createMock(Producer::class);
        $serializer = $this->messageSerializer();
        $consumer = $this->consumer();
        $clock = $this->clock();
        $source = $this->sourceTopic();

        $factory = new DeadLetterTopicFactory($producer, $serializer, $consumer, $clock);

        $this->assertEquals(
            new DeadLetterTopic($source, $producer, $serializer, $consumer, $factory, $clock),
            $factory->buildFor($source),
        );
    }

    private function deadLetterTopic(): DeadLetterTopic
    {
        $producer = $this->createMock(Producer::class);
        $serializer = $this->messageSerializer();
        $consumer = $this->consumer();
        $clock = $this->clock();

        return new DeadLetterTopic(
            $this->sourceTopic(),
            $producer,
            $serializer,
            $consumer,
            new DeadLetterTopicFactory($producer, $serializer, $consumer, $clock),
            $clock,
        );
    }

    private function messageSerializer(): AvroMessageSerializer
    {
        return new AvroMessageSerializer(
            $this->createMock(AvroSerializer::class),
            $this->createMock(DenormalizerInterface::class),
        );
    }

    private function sourceTopic(): Transport
    {
        $topic = $this->createMock(Transport::class);
        $topic->method("getName")->willReturn("user-events");

        return $topic;
    }

    private function clock(): Clock
    {
        return PausedClock::on(new DateTimeImmutable("2026-07-30T00:00:00+00:00"));
    }

    private function consumer(): Consumer
    {
        return new Consumer(
            $this->createMock(KafkaConsumer::class),
            $this->createMock(AvroSerializer::class),
            new NullLogger(),
        );
    }
}
