<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Psr\Log\LoggerInterface;
use Throwable;

class MessageProcessor
{
    private bool $shutdownRequested = false;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function shutdown(): void
    {
        $this->shutdownRequested = true;
    }

    public function process(Transport $transport): void
    {
        $worker = $transport->getWorker();
        $stream = $transport->receive();

        while ($stream->valid()) {
            $undeliverable = !$this->handleBatch($transport, $stream->current());

            if ($undeliverable || $this->shouldStop($worker)) {
                $stream->send(Signal::STOP);

                break;
            }

            $stream->next();
        }
    }

    /**
     * @param array<TransportMessage> $batch
     */
    private function handleBatch(Transport $transport, array $batch): bool
    {
        pcntl_sigprocmask(SIG_BLOCK, [SIGTERM, SIGINT]);

        try {
            foreach ($batch as $message) {
                if (!$this->handle($transport, $message)) {
                    return false;
                }

                $transport->getWorker()->countProcessedMessage();
            }
        } finally {
            pcntl_sigprocmask(SIG_UNBLOCK, [SIGTERM, SIGINT]);
            pcntl_signal_dispatch();
        }

        return true;
    }

    private function handle(Transport $transport, TransportMessage $message): bool
    {
        try {
            $transport->getWorker()->processMessage($message);
            $transport->ack($message);

            return true;
        } catch (Throwable $exception) {
            $this->logger->error("Message processing failed", [
                "transport" => $message->transport,
                "source" => $message->source,
                "event_type" => $message->eventType(),
                "exception" => $exception->getMessage(),
            ]);

            return $this->reject($transport, $message, $exception);
        }
    }

    private function reject(Transport $transport, TransportMessage $message, Throwable $exception): bool
    {
        try {
            $transport->reject($message, $exception);

            return true;
        } catch (Throwable $rejectionFailure) {
            $this->logger->critical("Consumer stopping: message could not be rejected", [
                "transport" => $message->transport,
                "source" => $message->source,
                "exception" => $rejectionFailure->getMessage(),
            ]);

            return false;
        }
    }

    private function shouldStop(Worker $worker): bool
    {
        if ($this->shutdownRequested) {
            $this->logger->info("Consumer stopping: shutdown signal received");

            return true;
        }

        if ($worker->maxIterationsReached()) {
            $this->logger->info("Consumer stopping: message limit reached", [
                "max_iterations" => $worker->getMaxIterations(),
            ]);

            return true;
        }

        if ($worker->maxLifeTimeReached()) {
            $this->logger->info("Consumer stopping: max lifetime reached", [
                "max_life_time" => $worker->getMaxLifeTime()->format(DATE_ATOM),
            ]);

            return true;
        }

        if ($worker->memoryLimitReached()) {
            $this->logger->info("Consumer stopping: memory limit reached", [
                "memory_limit_bytes" => $worker->getMemoryLimitInBytes(),
                "memory_usage_bytes" => memory_get_usage(true),
            ]);

            return true;
        }

        return false;
    }
}
