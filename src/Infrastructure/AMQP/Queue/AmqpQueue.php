<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Attribute\AsAmqpQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Signal;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Utils\Constants;
use Generator;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use ReflectionClass;
use RuntimeException;
use Throwable;

abstract class AmqpQueue implements Transport
{
    public const TRANSPORT = "amqp";
    public const RETRY_COUNT_HEADER = "x-retry-count";
    private const TWELVE_HOURS_IN_MS = 43_200_000;

    private ?AsAmqpQueue $amqpQueueAttribute = null;

    public function __construct(
        private readonly AMQPChannelFactory $AMQPChannelFactory,
        private readonly MessageSerializer $serializer,
        private readonly FailedQueueFactory $failedQueueFactory,
    ) {
        if ($attribute = (new ReflectionClass($this))->getAttributes(AsAmqpQueue::class)) {
            $this->amqpQueueAttribute = $attribute[0]->newInstance();
        }
    }

    public function getName(): string
    {
        return $this->attribute()->getName();
    }

    public function getNumberOfConsumers(): int
    {
        return $this->attribute()->getNumberOfWorkers();
    }

    public function send(Envelope $envelope): void
    {
        $message = new AMQPMessage($this->serializer->encode($envelope), $this->propertiesWithRetryCount(0));

        $this->getChannel()->basic_publish($message, "", $this->getName());
    }

    public function sendBatch(array $envelopes): void
    {
        if (empty($envelopes)) {
            return;
        }

        /** @phpstan-ignore-next-line */
        if (!empty(array_filter($envelopes, fn($envelope) => !$envelope instanceof Envelope))) {
            throw new RuntimeException(sprintf("All envelopes need to implement %s", Envelope::class));
        }

        $channel = $this->getChannel();

        foreach ($envelopes as $envelope) {
            $message = new AMQPMessage($this->serializer->encode($envelope), $this->propertiesWithRetryCount(0));

            $channel->batch_basic_publish($message, "", $this->getName());
        }
        $channel->publish_batch();
    }

    public function sendRawJson(Envelope $envelope): void
    {
        $message = new AMQPMessage(Json::encode($envelope->jsonSerialize()), $this->propertiesWithRetryCount(0));

        $this->getChannel()->basic_publish($message, "", $this->getName());
    }

    public function receive(): Generator
    {
        $channel = $this->getChannel();

        /** @var array<TransportMessage> $batch */
        $batch = [];

        $consumerTag = $channel->basic_consume(
            $this->getName(),
            "",
            false,
            false,
            false,
            false,
            function (AMQPMessage $delivery) use (&$batch): void {
                $batch[] = $this->toTransportMessage($delivery);
            },
        );

        try {
            while ($channel->is_open()) {
                $channel->wait();

                if ($batch === []) {
                    continue;
                }

                $delivered = $batch;
                $batch = [];

                if ((yield $delivered) === Signal::STOP) {
                    return;
                }
            }
        } finally {
            $this->stopConsuming($channel, $consumerTag);
        }
    }

    public function ack(TransportMessage $message): void
    {
        $delivery = $this->deliveryOf($message);

        $delivery->getChannel()?->basic_ack($delivery->getDeliveryTag());
    }

    public function reject(TransportMessage $message, Throwable $exception): void
    {
        $delivery = $this->deliveryOf($message);
        $retryCount = (int)($message->header(self::RETRY_COUNT_HEADER) ?? 0);

        if ($retryCount >= Constants::MAX_RETRY_COUNT) {
            $this->getWorker()->processFailure($message, $exception, $this);
            $this->failedQueueFactory->buildFor($this)->send($this->envelopeOf($message));
            $this->ack($message);

            return;
        }

        $retry = new AMQPMessage($delivery->getBody(), $this->propertiesWithRetryCount($retryCount + 1));

        $delivery->getChannel()?->basic_publish($retry, "", $delivery->getRoutingKey());
        $this->ack($message);
    }

    protected function getChannel(): AMQPChannel
    {
        return $this->AMQPChannelFactory->getForQueue($this);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesWithRetryCount(int $retryCount): array
    {
        return [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => self::TWELVE_HOURS_IN_MS,
            "application_headers" => new AMQPTable([
                self::RETRY_COUNT_HEADER => $retryCount,
            ]),
        ];
    }

    private function toTransportMessage(AMQPMessage $delivery): TransportMessage
    {
        $headers = $this->headersOf($delivery);

        return new TransportMessage(
            body: $this->serializer->decode($delivery->getBody(), $headers),
            headers: $headers,
            transport: self::TRANSPORT,
            source: $this->getName(),
            handle: $delivery,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headersOf(AMQPMessage $delivery): array
    {
        $headers = $delivery->get_properties()["application_headers"] ?? null;

        if ($headers instanceof AMQPTable) {
            $headers = $headers->getNativeData();
        }

        return is_array($headers) ? array_map(strval(...), $headers) : [];
    }

    private function stopConsuming(AMQPChannel $channel, string $consumerTag): void
    {
        if (!$channel->is_open()) {
            return;
        }

        $channel->basic_cancel($consumerTag);
        $channel->close();
    }

    private function envelopeOf(TransportMessage $message): Envelope
    {
        if (!$message->body instanceof Envelope) {
            throw new RuntimeException(sprintf('Message from queue "%s" carries no envelope and cannot be moved to the failed queue', $this->getName()));
        }

        return $message->body;
    }

    private function deliveryOf(TransportMessage $message): AMQPMessage
    {
        if (!$message->handle instanceof AMQPMessage) {
            throw new RuntimeException(sprintf('Message from transport "%s" was not delivered by %s', $message->transport, self::class));
        }

        return $message->handle;
    }

    private function attribute(): AsAmqpQueue
    {
        if (!$this->amqpQueueAttribute) {
            throw new RuntimeException("AsAmqpQueue attribute not set");
        }

        return $this->amqpQueueAttribute;
    }
}
