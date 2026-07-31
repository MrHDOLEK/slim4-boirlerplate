<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Signal;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use App\Infrastructure\Messaging\Worker;
use Generator;
use RuntimeException;
use Throwable;

final class RecordingTransport implements Transport
{
    /** @var array<TransportMessage> */
    public array $acknowledged = [];

    /** @var array<TransportMessage> */
    public array $rejected = [];

    public bool $consumingStopped = false;
    public bool $rejectionFails = false;

    /**
     * @param array<array<TransportMessage>> $batches
     */
    public function __construct(
        private readonly Worker $worker,
        private readonly array $batches,
    ) {}

    public function getName(): string
    {
        return "recording-transport";
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }

    public function getNumberOfConsumers(): int
    {
        return 1;
    }

    public function send(Envelope $envelope): void
    {
    }

    public function sendBatch(array $envelopes): void
    {
    }

    public function receive(): Generator
    {
        try {
            foreach ($this->batches as $batch) {
                if ((yield $batch) === Signal::STOP) {
                    return;
                }
            }
        } finally {
            $this->consumingStopped = true;
        }
    }

    public function ack(TransportMessage $message): void
    {
        $this->acknowledged[] = $message;
    }

    public function reject(TransportMessage $message, Throwable $exception): void
    {
        if ($this->rejectionFails) {
            throw new RuntimeException("the message could not be rejected");
        }

        $this->rejected[] = $message;
    }
}
