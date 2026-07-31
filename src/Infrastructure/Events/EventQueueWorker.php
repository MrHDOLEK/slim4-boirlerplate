<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Infrastructure\Messaging\BaseWorker;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use Lcobucci\Clock\Clock;
use RuntimeException;
use Throwable;

class EventQueueWorker extends BaseWorker
{
    public function __construct(
        private readonly EventBus $eventBus,
        Clock $clock,
    ) {
        parent::__construct($clock);
    }

    public function getName(): string
    {
        return "event-queue-worker";
    }

    public function processMessage(TransportMessage $message): void
    {
        $this->eventBus->dispatch($this->eventOf($message));
    }

    public function processFailure(TransportMessage $message, Throwable $exception, Transport $transport): void
    {
        $this->eventOf($message)->setMetaData([
            "exceptionMessage" => $exception->getMessage(),
            "traceAsString" => $exception->getTraceAsString(),
        ]);
    }

    private function eventOf(TransportMessage $message): DomainEvent
    {
        if (!$message->body instanceof DomainEvent) {
            throw new RuntimeException(sprintf("%s can only handle events, %s given", self::class, get_debug_type($message->body)));
        }

        return $message->body;
    }
}
