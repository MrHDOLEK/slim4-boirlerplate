<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging;

use App\Infrastructure\Messaging\MessageProcessor;
use App\Infrastructure\Messaging\TransportMessage;
use App\Infrastructure\Messaging\Worker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class MessageProcessorTest extends TestCase
{
    private Worker&MockObject $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = $this->createMock(Worker::class);
        $this->worker->method("getName")->willReturn("recording-worker");
        $this->worker->method("getMaxIterations")->willReturn(1);
    }

    public function testItAcknowledgesEveryMessageItProcessed(): void
    {
        $first = $this->message("first");
        $second = $this->message("second");
        $this->stopsAfterTheFirstBatch();

        $this->worker
            ->expects($this->exactly(2))
            ->method("countProcessedMessage");

        $transport = new RecordingTransport($this->worker, [[$first, $second]]);
        (new MessageProcessor(new NullLogger()))->process($transport);

        $this->assertSame([$first, $second], $transport->acknowledged);
        $this->assertSame([], $transport->rejected);
        $this->assertTrue($transport->consumingStopped);
    }

    public function testItRejectsAMessageTheWorkerCouldNotProcess(): void
    {
        $message = $this->message("boom");
        $this->stopsAfterTheFirstBatch();

        $this->worker
            ->method("processMessage")
            ->willThrowException(new RuntimeException("handler blew up"));

        $transport = new RecordingTransport($this->worker, [[$message]]);
        (new MessageProcessor(new NullLogger()))->process($transport);

        $this->assertSame([$message], $transport->rejected);
        $this->assertSame([], $transport->acknowledged);
    }

    public function testItStopsWithoutTouchingTheMessagesItNeverPulled(): void
    {
        $first = $this->message("first");
        $second = $this->message("second");
        $this->stopsAfterTheFirstBatch();

        $transport = new RecordingTransport($this->worker, [[$first], [$second]]);
        (new MessageProcessor(new NullLogger()))->process($transport);

        $this->assertSame([$first], $transport->acknowledged);
        $this->assertSame([], $transport->rejected);
        $this->assertTrue($transport->consumingStopped);
    }

    public function testItKeepsConsumingWhileNoLimitIsReached(): void
    {
        $first = $this->message("first");
        $second = $this->message("second");

        $this->worker->method("maxIterationsReached")->willReturn(false);
        $this->worker->method("maxLifeTimeReached")->willReturn(false);
        $this->worker->method("memoryLimitReached")->willReturn(false);

        $transport = new RecordingTransport($this->worker, [[$first], [$second]]);
        (new MessageProcessor(new NullLogger()))->process($transport);

        $this->assertSame([$first, $second], $transport->acknowledged);
    }

    public function testItStopsWhenTheMessageCanNotBeRejected(): void
    {
        $first = $this->message("first");
        $second = $this->message("second");

        $this->worker->method("maxIterationsReached")->willReturn(false);
        $this->worker->method("maxLifeTimeReached")->willReturn(false);
        $this->worker->method("memoryLimitReached")->willReturn(false);
        $this->worker
            ->method("processMessage")
            ->willThrowException(new RuntimeException("handler blew up"));

        $transport = new RecordingTransport($this->worker, [[$first], [$second]]);
        $transport->rejectionFails = true;

        (new MessageProcessor(new NullLogger()))->process($transport);

        $this->assertSame([], $transport->acknowledged);
        $this->assertSame([], $transport->rejected);
        $this->assertTrue($transport->consumingStopped);
    }

    public function testItStopsAfterTheCurrentBatchWhenShutdownWasRequested(): void
    {
        $first = $this->message("first");
        $second = $this->message("second");

        $this->worker->method("maxIterationsReached")->willReturn(false);
        $this->worker->method("maxLifeTimeReached")->willReturn(false);
        $this->worker->method("memoryLimitReached")->willReturn(false);

        $processor = new MessageProcessor(new NullLogger());
        $this->worker
            ->method("processMessage")
            ->willReturnCallback(static function () use ($processor): void {
                $processor->shutdown();
            });

        $transport = new RecordingTransport($this->worker, [[$first], [$second]]);
        $processor->process($transport);

        $this->assertSame([$first], $transport->acknowledged);
    }

    private function stopsAfterTheFirstBatch(): void
    {
        $this->worker->method("maxIterationsReached")->willReturn(true);
    }

    private function message(string $body): TransportMessage
    {
        return new TransportMessage($body, [], "recording", "recording-transport");
    }
}
