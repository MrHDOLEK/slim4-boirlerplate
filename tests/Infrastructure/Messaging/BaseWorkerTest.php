<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\BaseWorker;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use PHPUnit\Framework\TestCase;
use Tests\PausedClock;
use Throwable;

class BaseWorkerTest extends TestCase
{
    public function testMessageLimitTriggersAfterExactlyThatManyMessages(): void
    {
        $worker = $this->worker(3);

        $this->assertFalse($worker->maxIterationsReached());

        $worker->countProcessedMessage();
        $worker->countProcessedMessage();
        $this->assertFalse($worker->maxIterationsReached(), "must not stop before the limit");

        $worker->countProcessedMessage();
        $this->assertTrue($worker->maxIterationsReached(), "must stop on the third message");
    }

    public function testCheckingTheLimitDoesNotAdvanceTheCounter(): void
    {
        $worker = $this->worker(2);

        $worker->countProcessedMessage();

        $this->assertFalse($worker->maxIterationsReached());
        $this->assertFalse($worker->maxIterationsReached());
        $this->assertFalse($worker->maxIterationsReached());
    }

    public function testMemoryLimitIsExposedAndEvaluated(): void
    {
        $worker = $this->worker(10);

        $this->assertSame(192 * 1024 * 1024, $worker->getMemoryLimitInBytes());
        $this->assertFalse($worker->memoryLimitReached());
    }

    private function worker(int $maxIterations): BaseWorker
    {
        return new class(PausedClock::on(new DateTimeImmutable("2026-07-30")), $maxIterations) extends BaseWorker {
            public function __construct(
                Clock $clock,
                private int $limit,
            ) {
                parent::__construct($clock);
            }

            public function getMaxIterations(): int
            {
                return $this->limit;
            }

            public function getName(): string
            {
                return "test-worker";
            }

            public function processMessage(TransportMessage $message): void
            {
            }

            public function processFailure(TransportMessage $message, Throwable $exception, Transport $transport): void
            {
            }
        };
    }
}
