<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Kafka;

use App\Infrastructure\Kafka\KafkaMessage;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Kafka\Worker\BaseKafkaWorker;
use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use PHPUnit\Framework\TestCase;
use Tests\PausedClock;
use Throwable;

class BaseKafkaWorkerTest extends TestCase
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

    private function worker(int $maxIterations): BaseKafkaWorker
    {
        return new class(PausedClock::on(new DateTimeImmutable("2026-07-30")), $maxIterations) extends BaseKafkaWorker {
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

            public function processMessage(KafkaMessage $message): void
            {
            }

            public function processFailure(KafkaMessage $message, Throwable $exception, KafkaTopic $topic): void
            {
            }
        };
    }
}
