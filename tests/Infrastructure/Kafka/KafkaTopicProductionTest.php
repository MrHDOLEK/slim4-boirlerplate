<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Exception\ProducerFailure;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Serializer\EncodedRecord;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopicFactory;
use App\Infrastructure\Messaging\Worker;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Tests\PausedClock;
use Tests\Support\RunUnitTester;

class KafkaTopicProductionTest extends TestCase
{
    private Producer&MockObject $producer;
    private ProducerTopic&MockObject $producerTopic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = $this->createMock(Producer::class);
        $this->producerTopic = $this->createMock(ProducerTopic::class);

        $this->producer->method("newTopic")->willReturn($this->producerTopic);
    }

    public function testItFlushesTheProducerBeforeReportingSuccess(): void
    {
        $this->producerTopic
            ->expects($this->once())
            ->method("producev");

        $this->producer
            ->expects($this->once())
            ->method("flush")
            ->with(10_000)
            ->willReturn(RD_KAFKA_RESP_ERR_NO_ERROR);

        $this->topic()->send(new RunUnitTester());
    }

    public function testItFailsWhenTheProducerCouldNotFlush(): void
    {
        $this->producer
            ->method("flush")
            ->willReturn(RD_KAFKA_RESP_ERR__TIMED_OUT);

        $this->expectException(ProducerFailure::class);
        $this->expectExceptionMessage('Could not flush the producer for topic "user-events"');

        $this->topic()->send(new RunUnitTester());
    }

    public function testItDoesNotTouchTheProducerForAnEmptyBatch(): void
    {
        $this->producer
            ->expects($this->never())
            ->method("flush");

        $this->topic()->sendBatch([]);
    }

    private function topic(): TestKafkaTopic
    {
        $avroSerializer = $this->createMock(AvroSerializer::class);
        $avroSerializer
            ->method("encode")
            ->willReturn(new EncodedRecord("encoded", "user-events-value", 7, 1));

        return new TestKafkaTopic(
            $this->producer,
            new AvroMessageSerializer($avroSerializer, $this->createMock(DenormalizerInterface::class)),
            new Consumer($this->createMock(KafkaConsumer::class), $avroSerializer, new NullLogger()),
            $this->createMock(DeadLetterTopicFactory::class),
            PausedClock::on(new DateTimeImmutable("2026-07-30T00:00:00+00:00")),
            $this->createMock(Worker::class),
        );
    }
}
