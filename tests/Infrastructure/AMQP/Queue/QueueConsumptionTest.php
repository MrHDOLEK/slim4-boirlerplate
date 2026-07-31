<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\Messaging\Exception\MessageSerializationFailure;
use App\Infrastructure\Messaging\MessageProcessor;
use App\Infrastructure\Messaging\Serializer\NativePhpMessageSerializer;
use App\Infrastructure\Messaging\Signal;
use App\Infrastructure\Messaging\TransportMessage;
use App\Infrastructure\Messaging\Worker;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Infrastructure\Messaging\Serializer\OutsideTheAllowlist;
use Tests\Support\RunUnitTester;

class QueueConsumptionTest extends TestCase
{
    private AMQPChannelFactory&MockObject $AMQPChannelFactory;
    private AMQPChannel&MockObject $channel;
    private Worker&MockObject $worker;
    private TestQueue $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AMQPChannelFactory = $this->createMock(AMQPChannelFactory::class);
        $this->channel = $this->createMock(AMQPChannel::class);
        $this->worker = $this->createMock(Worker::class);

        $this->AMQPChannelFactory->method("getForQueue")->willReturn($this->channel);

        $this->queue = new TestQueue(
            $this->AMQPChannelFactory,
            $this->worker,
            (new NativePhpMessageSerializer())->withAllowedClasses([RunUnitTester::class]),
        );
    }

    public function testItYieldsDeliveriesAsTransportMessages(): void
    {
        $envelope = new RunUnitTester();
        $this->deliverOnEveryWait($this->delivery(serialize($envelope), 2));

        $stream = $this->queue->receive();
        $batch = $stream->current();

        $this->assertCount(1, $batch);

        $message = $batch[0];
        $this->assertInstanceOf(TransportMessage::class, $message);
        $this->assertEquals($envelope, $message->body);
        $this->assertSame("amqp", $message->transport);
        $this->assertSame("test-queue", $message->source);
        $this->assertSame("2", $message->header("x-retry-count"));
    }

    public function testItCancelsTheConsumerAndClosesTheChannelWhenStopped(): void
    {
        $this->deliverOnEveryWait($this->delivery(serialize(new RunUnitTester()), 0));

        $this->channel
            ->expects($this->once())
            ->method("basic_cancel")
            ->with("consumer-tag");

        $this->channel
            ->expects($this->once())
            ->method("close");

        $stream = $this->queue->receive();
        $stream->current();
        $stream->send(Signal::STOP);

        $this->assertFalse($stream->valid());
    }

    public function testAckAcknowledgesTheDelivery(): void
    {
        $this->channel
            ->expects($this->once())
            ->method("basic_ack")
            ->with("delivery-tag");

        $this->queue->ack($this->transportMessage(0));
    }

    public function testRejectRepublishesTheMessageWithAnIncreasedRetryCount(): void
    {
        $body = serialize(new RunUnitTester());

        $published = null;
        $this->channel
            ->expects($this->once())
            ->method("basic_publish")
            ->willReturnCallback(function (AMQPMessage $message, string $exchange, string $routingKey) use (&$published): void {
                $published = [$message, $exchange, $routingKey];
            });

        $this->channel
            ->expects($this->once())
            ->method("basic_ack")
            ->with("delivery-tag");

        $this->worker
            ->expects($this->never())
            ->method("processFailure");

        $this->queue->reject($this->transportMessage(2, $body), new RuntimeException("boom"));

        $this->assertSame($body, $published[0]->getBody());
        $this->assertSame("", $published[1]);
        $this->assertSame("test-queue", $published[2]);
        $this->assertSame(["x-retry-count" => 3], $published[0]->get_properties()["application_headers"]->getNativeData());
    }

    public function testRejectMovesTheMessageToTheFailedQueueOnceTheRetriesAreExhausted(): void
    {
        $envelope = new RunUnitTester();
        $exception = new RuntimeException("boom");
        $message = $this->transportMessage(3, serialize($envelope), $envelope);

        $published = null;
        $this->channel
            ->expects($this->once())
            ->method("basic_publish")
            ->willReturnCallback(function (AMQPMessage $message, string $exchange, string $routingKey) use (&$published): void {
                $published = [$message, $exchange, $routingKey];
            });

        $this->worker
            ->expects($this->once())
            ->method("processFailure")
            ->with($message, $exception, $this->queue);

        $this->channel
            ->expects($this->once())
            ->method("basic_ack")
            ->with("delivery-tag");

        $this->queue->reject($message, $exception);

        $this->assertSame(serialize($envelope), $published[0]->getBody());
        $this->assertSame("", $published[1]);
        $this->assertSame("test-queue-failed", $published[2]);
    }

    public function testRejectRefusesToMoveAMessageThatCarriesNoEnvelope(): void
    {
        $this->worker
            ->expects($this->once())
            ->method("processFailure");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Message from queue "test-queue" carries no envelope');

        $this->queue->reject($this->transportMessage(3), new RuntimeException("boom"));
    }

    public function testItRefusesMessagesThatWereNotDeliveredByThisTransport(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("was not delivered by");

        $this->queue->ack(new TransportMessage("body", [], "another-broker", "user-events"));
    }

    public function testItRefusesABodyItPublishedItselfWhenTheClassIsOutsideTheAllowlist(): void
    {
        $published = null;
        $this->channel
            ->method("basic_publish")
            ->willReturnCallback(function (AMQPMessage $message) use (&$published): void {
                $published = $message;
            });

        $this->queue->send(new OutsideTheAllowlist());

        $this->assertSame(serialize(new OutsideTheAllowlist()), $published->getBody());

        $this->deliverOnEveryWait($this->delivery($published->getBody(), 0));

        $this->expectException(MessageSerializationFailure::class);
        $this->expectExceptionMessage(OutsideTheAllowlist::class);

        $this->queue->receive()->current();
    }

    public function testTheWorkerNeverSeesADeliveryWhoseClassIsOutsideTheAllowlist(): void
    {
        $this->deliverOnEveryWait($this->delivery(serialize(new OutsideTheAllowlist()), 0));

        $this->worker
            ->expects($this->never())
            ->method("processMessage");

        $this->expectException(MessageSerializationFailure::class);

        (new MessageProcessor(new NullLogger()))->process($this->queue);
    }

    private function transportMessage(int $retryCount, string $body = "body", mixed $decoded = null): TransportMessage
    {
        $delivery = $this->delivery($body, $retryCount);

        return new TransportMessage(
            body: $decoded ?? $body,
            headers: ["x-retry-count" => (string)$retryCount],
            transport: "amqp",
            source: "test-queue",
            handle: $delivery,
        );
    }

    private function delivery(string $body, int $retryCount): AMQPMessage
    {
        $delivery = new AMQPMessage($body, [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "application_headers" => new AMQPTable(["x-retry-count" => $retryCount]),
        ]);
        $delivery->setChannel($this->channel);
        $delivery->setDeliveryInfo("delivery-tag", false, "", "test-queue");

        return $delivery;
    }

    private function deliverOnEveryWait(AMQPMessage $delivery): void
    {
        $consume = null;

        $this->channel
            ->method("basic_consume")
            ->willReturnCallback(function (...$arguments) use (&$consume): string {
                $consume = $arguments[6];

                return "consumer-tag";
            });

        $this->channel->method("is_open")->willReturn(true);

        $this->channel
            ->method("wait")
            ->willReturnCallback(function () use (&$consume, $delivery): void {
                $consume($delivery);
            });
    }
}
