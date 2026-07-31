<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Signal;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use App\Infrastructure\Messaging\Worker;
use DateTimeImmutable;
use Generator;
use Tests\PausedClock;
use Throwable;

class InMemoryTransport implements Transport
{
    /** @var array<Envelope> */
    public array $sent = [];

    /** @var array<TransportMessage> */
    public array $acknowledged = [];

    /** @var array<TransportMessage> */
    public array $rejected = [];

    private Worker $worker;

    public function __construct(
        private readonly string $name = "test-transport",
        ?Worker $worker = null,
        private readonly int $numberOfConsumers = 1,
    ) {
        $this->worker = $worker ?? new InMemoryWorker(PausedClock::on(new DateTimeImmutable("2022-07-01")));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }

    public function getNumberOfConsumers(): int
    {
        return $this->numberOfConsumers;
    }

    public function send(Envelope $envelope): void
    {
        $this->sent[] = $envelope;
    }

    public function sendBatch(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            $this->send($envelope);
        }
    }

    public function receive(): Generator
    {
        if ((yield []) === Signal::STOP) {
            return;
        }
    }

    public function ack(TransportMessage $message): void
    {
        $this->acknowledged[] = $message;
    }

    public function reject(TransportMessage $message, Throwable $exception): void
    {
        $this->rejected[] = $message;
    }
}
