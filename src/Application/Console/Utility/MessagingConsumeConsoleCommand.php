<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use App\Infrastructure\Messaging\MessageProcessor;
use App\Infrastructure\Messaging\TransportContainer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "app:messaging:consume",
    description: "Start consuming a given transport",
)]
class MessagingConsumeConsoleCommand extends ConsoleCommand implements SignalableCommandInterface
{
    public function __construct(
        private readonly TransportContainer $transportContainer,
        private readonly MessageProcessor $processor,
    ) {
        parent::__construct();
    }

    /**
     * @return array<int>
     */
    public function getSubscribedSignals(): array
    {
        return [SIGTERM, SIGINT];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->processor->shutdown();

        return false;
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument("transport", InputArgument::REQUIRED, "The queue or topic to consume."),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transport = $this->transportContainer->getTransport($input->getArgument("transport"));
        $this->processor->process($transport);

        return Command::SUCCESS;
    }
}
