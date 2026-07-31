<?php

declare(strict_types=1);

namespace Tests\Application\Console;

use App\Application\Console\Utility\MessagingConsumeConsoleCommand;
use App\Infrastructure\Messaging\MessageProcessor;
use App\Infrastructure\Messaging\TransportContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\ConsoleCommandTestCase;
use Tests\Support\InMemoryTransport;

class MessagingConsumeConsoleCommandTest extends ConsoleCommandTestCase
{
    private MessagingConsumeConsoleCommand $messagingConsumeConsoleCommand;
    private MockObject $transportContainer;
    private MockObject $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportContainer = $this->createMock(TransportContainer::class);
        $this->processor = $this->createMock(MessageProcessor::class);

        $this->messagingConsumeConsoleCommand = new MessagingConsumeConsoleCommand(
            $this->transportContainer,
            $this->processor,
        );
    }

    public function testExecute(): void
    {
        $transport = new InMemoryTransport();

        $this->transportContainer
            ->expects($this->once())
            ->method("getTransport")
            ->with("test-transport")
            ->willReturn($transport);

        $this->processor
            ->expects($this->once())
            ->method("process")
            ->with($transport);

        $command = $this->getCommandInApplication("app:messaging:consume");

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            "command" => $command->getName(),
            "transport" => "test-transport",
        ]);
    }

    public function testItDoesNotAdvertiseBrokerSpecificAliases(): void
    {
        $this->assertSame([], $this->messagingConsumeConsoleCommand->getAliases());
    }

    public function testGetSubscribedSignals(): void
    {
        /** @var SignalableCommandInterface $command */
        $command = $this->getCommandInApplication("app:messaging:consume");
        $this->assertEquals([SIGTERM, SIGINT], $command->getSubscribedSignals());
    }

    public function testHandleSignal(): void
    {
        /** @var SignalableCommandInterface $command */
        $command = $this->getCommandInApplication("app:messaging:consume");

        $this->processor
            ->expects($this->once())
            ->method("shutdown");

        $command->handleSignal(1);
    }

    protected function getConsoleCommand(): Command
    {
        return $this->messagingConsumeConsoleCommand;
    }
}
