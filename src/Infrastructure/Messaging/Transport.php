<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Generator;
use Throwable;

interface Transport
{
    public function getName(): string;

    public function getWorker(): Worker;

    public function getNumberOfConsumers(): int;

    public function send(Envelope $envelope): void;

    /**
     * @param array<Envelope> $envelopes
     */
    public function sendBatch(array $envelopes): void;

    /**
     * @return Generator<int, array<TransportMessage>, Signal|null, void>
     */
    public function receive(): Generator;

    public function ack(TransportMessage $message): void;

    public function reject(TransportMessage $message, Throwable $exception): void;
}
