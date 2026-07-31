<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterRecord;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopicFactory;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\MessageProcessor;
use App\Infrastructure\Messaging\Worker;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer;
use RdKafka\Message as RdKafkaMessage;
use RdKafka\Producer;
use RdKafka\TopicPartition;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Tests\PausedClock;

class KafkaTopicConsumptionTest extends TestCase
{
    private MockObject $kafkaConsumer;
    private MockObject $deadLetterTopic;
    private MockObject $deadLetterTopicFactory;
    private MockObject $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kafkaConsumer = $this->createMock(KafkaConsumer::class);
        $this->deadLetterTopic = $this->createMock(KafkaTopic::class);
        $this->deadLetterTopicFactory = $this->createMock(DeadLetterTopicFactory::class);
        $this->worker = $this->createMock(Worker::class);

        $this->deadLetterTopic->method("getName")->willReturn("user-events-dead-letter");
        $this->deadLetterTopicFactory->method("buildFor")->willReturn($this->deadLetterTopic);
        $this->worker->method("maxIterationsReached")->willReturn(true);
        $this->worker->method("getMaxIterations")->willReturn(1);
    }

    public function testItRoutesAFailedMessageToTheDeadLetterTopic(): void
    {
        $this->worker
            ->method("processMessage")
            ->willThrowException(new RuntimeException("handler blew up"));

        $produced = null;

        $this->deadLetterTopic
            ->expects($this->once())
            ->method("send")
            ->willReturnCallback(function (Envelope $envelope) use (&$produced): void {
                $produced = $envelope;
            });

        $this->worker
            ->expects($this->once())
            ->method("processFailure");

        $this->process();

        $this->assertInstanceOf(DeadLetterRecord::class, $produced);
        $this->assertSame([
            "originalTopic" => "user-events",
            "originalPartition" => 3,
            "originalOffset" => 41,
            "originalKey" => "jdoe",
            "originalTimestamp" => 1_700_000_000,
            "originalHeaders" => ["event-type" => "UserWasCreated", "schema-id" => "7"],
            "payload" => '{"eventName":"UserWasCreated"}',
            "failureClass" => RuntimeException::class,
            "failureReason" => "handler blew up",
            "failedAt" => "2026-07-30T00:00:00+00:00",
        ], $produced->jsonSerialize());
    }

    public function testItCommitsTheNextOffsetOnceTheDeadLetterWriteSucceeded(): void
    {
        $this->worker
            ->method("processMessage")
            ->willThrowException(new RuntimeException("handler blew up"));

        $committed = [];

        $this->kafkaConsumer
            ->expects($this->once())
            ->method("commit")
            ->willReturnCallback(function (array $offsets) use (&$committed): void {
                $committed = $offsets;
            });

        $this->process();

        $this->assertCount(1, $committed);
        $this->assertInstanceOf(TopicPartition::class, $committed[0]);
        $this->assertSame("user-events", $committed[0]->getTopic());
        $this->assertSame(3, $committed[0]->getPartition());
        $this->assertSame(42, $committed[0]->getOffset());
    }

    public function testItCommitsTheNextOffsetOfASuccessfullyProcessedMessage(): void
    {
        $committed = [];

        $this->kafkaConsumer
            ->expects($this->once())
            ->method("commit")
            ->willReturnCallback(function (array $offsets) use (&$committed): void {
                $committed = $offsets;
            });

        $this->deadLetterTopic
            ->expects($this->never())
            ->method("send");

        $this->process();

        $this->assertCount(1, $committed);
        $this->assertSame(42, $committed[0]->getOffset());
    }

    public function testItDoesNotCommitWhenTheDeadLetterWriteFails(): void
    {
        $this->worker
            ->method("processMessage")
            ->willThrowException(new RuntimeException("handler blew up"));

        $this->deadLetterTopic
            ->method("send")
            ->willThrowException(new RuntimeException("dead letter broker unreachable"));

        $this->worker
            ->expects($this->never())
            ->method("processFailure");

        $this->kafkaConsumer
            ->expects($this->never())
            ->method("commit");

        $this->kafkaConsumer
            ->expects($this->once())
            ->method("close");

        $this->process();
    }

    private function process(): void
    {
        $serializer = $this->createMock(AvroSerializer::class);
        $serializer->method("decode")->willReturn(["eventName" => "UserWasCreated"]);

        $this->kafkaConsumer
            ->method("consume")
            ->willReturnOnConsecutiveCalls(
                $this->record(),
                $this->endOfPartition(),
            );

        $topic = new TestKafkaTopic(
            $this->createMock(Producer::class),
            new AvroMessageSerializer($serializer, $this->createMock(DenormalizerInterface::class)),
            new Consumer($this->kafkaConsumer, $serializer, new NullLogger()),
            $this->deadLetterTopicFactory,
            PausedClock::on(new DateTimeImmutable("2026-07-30T00:00:00+00:00")),
            $this->worker,
        );

        (new MessageProcessor(new NullLogger()))->process($topic);
    }

    private function record(): RdKafkaMessage
    {
        $message = new RdKafkaMessage();
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->topic_name = "user-events";
        $message->partition = 3;
        $message->offset = 41;
        $message->payload = "encoded";
        $message->headers = ["event-type" => "UserWasCreated", "schema-id" => "7"];
        $message->key = "jdoe";
        $message->timestamp = 1_700_000_000;

        return $message;
    }

    private function endOfPartition(): RdKafkaMessage
    {
        $message = new RdKafkaMessage();
        $message->err = RD_KAFKA_RESP_ERR__PARTITION_EOF;

        return $message;
    }
}
