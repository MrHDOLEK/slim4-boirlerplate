<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP;

use App\Infrastructure\AMQP\Queue\Queue;
use App\Infrastructure\AMQP\Worker\WorkerMaxLifeTimeOrIterationsExceeded;
use App\Infrastructure\Utils\Constants;
use Doctrine\DBAL\Exception\ConnectionLost;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

class Consumer
{
    private const int TWELVE_HOURS_IN_MS = 43200000;

    private ?AMQPChannel $channel = null;
    private bool $forceShutDown = false;

    public function __construct(
        private readonly AMQPStreamConnectionFactory $AMQPStreamConnectionFactory,
        private readonly AMQPChannelFactory $AMQPChannelFactory,
    ) {}

    public function __destruct()
    {
        $this->channel?->close();
    }

    public function shutdown(): void
    {
        $this->forceShutDown = true;
    }

    public function consume(Queue $queue): void
    {
        $channel = $this->AMQPChannelFactory->getForQueue($queue);

        $callback = static function (AMQPMessage $message) use ($queue): void {
            // Block any incoming exit signals to make sure the current message can be processed.
            pcntl_sigprocmask(SIG_BLOCK, [SIGTERM, SIGINT]);
            self::consumeCallback($message, $queue);
            // Unblock any incoming exit signals, message has been processed, consumer can DIE.
            pcntl_sigprocmask(SIG_UNBLOCK, [SIGTERM, SIGINT]);
            // Dispatch the exit signals that might've come in.
            pcntl_signal_dispatch();
        };

        try {
            $channel->basic_consume($queue->getName(), "", false, false, false, false, $callback);

            while ($channel->is_open() && !$this->forceShutDown) {
                $channel->wait();
                // Dispatch incoming exit signals.
                pcntl_signal_dispatch();
            }
        } catch (WorkerMaxLifeTimeOrIterationsExceeded|ConnectionLost) {
            $channel->close();
            $this->AMQPStreamConnectionFactory->get()->close();
        }
    }

    /**
     * @throws ConnectionLost
     */
    public function forwardEvents(Queue $sourceQueue, Queue $targetQueueName): void
    {
        $sourceChannel = $this->AMQPChannelFactory->getForQueue($sourceQueue);
        $targetChannel = $this->AMQPChannelFactory->getForQueue($targetQueueName);

        $callback = function (AMQPMessage $message) use ($targetChannel, $targetQueueName): void {
            try {
                $properties = [
                    "content_type" => "text/plain",
                    "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    "expiration" => self::TWELVE_HOURS_IN_MS,
                ];

                $msg = new AMQPMessage(
                    $message->getBody(),
                    $properties,
                );
                $targetChannel->basic_publish($msg, "", $targetQueueName->getName());
                $message->getChannel()?->basic_ack($message->getDeliveryTag());
            } catch (Throwable $exception) {
                $message->getChannel()?->basic_nack($message->getDeliveryTag(), false, false);
            }
        };

        try {
            $sourceChannel->basic_consume($sourceQueue->getName(), "", false, false, false, false, $callback);

            while ($sourceChannel->is_open() && !$this->forceShutDown) {
                $sourceChannel->wait();
                pcntl_signal_dispatch();
            }
        } catch (WorkerMaxLifeTimeOrIterationsExceeded|ConnectionLost $exception) {
            $sourceChannel->close();
            $targetChannel->close();
            $this->AMQPStreamConnectionFactory->get()->close();

            throw $exception;
        }
    }

    public static function consumeCallback(
        AMQPMessage $message,
        Queue $queue,
    ): void {
        $worker = $queue->getWorker();
        $envelope = unserialize($message->getBody());

        try {
            if ($worker->maxLifeTimeReached() || $worker->maxIterationsReached()) {
                throw new WorkerMaxLifeTimeOrIterationsExceeded();
            }

            $worker->processMessage($envelope, $message);
            $message->getChannel()?->basic_ack($message->getDeliveryTag());
        } catch (WorkerMaxLifeTimeOrIterationsExceeded $exception) {
            // Requeue message to make sure next consumer can process it.
            $message->getChannel()?->basic_nack($message->getDeliveryTag(), false, true);

            throw $exception;
        } catch (Throwable $exception) {
            $retry = (int)$message->get_properties()["application_headers"]["x-retry-count"];

            if ($retry >= Constants::MAX_RETRY_COUNT) {
                $worker->processFailure($envelope, $message, $exception, $queue);
                // Ack the message to unblock queue. Worker should handle failed messages.
                $message->getChannel()?->basic_ack($message->getDeliveryTag());

                return;
            }

            $properties = [
                "content_type" => "text/plain",
                "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                "expiration" => self::TWELVE_HOURS_IN_MS,
                "application_headers" => new AMQPTable([
                    "x-retry-count" => $retry + 1,
                ]),
            ];

            $messageRetry = new AMQPMessage($message->getBody(), $properties);
            $message->getChannel()?->basic_publish($messageRetry, "", $message->getRoutingKey());
            $message->getChannel()?->basic_ack($message->getDeliveryTag());
        }
    }
}
