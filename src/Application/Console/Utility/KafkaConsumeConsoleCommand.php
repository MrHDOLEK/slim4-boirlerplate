<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use App\Infrastructure\Kafka\Topic\KafkaTopicContainer;
use App\Infrastructure\Kafka\TopicProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "app:kafka:consume", description: "Start consuming a given Kafka topic")]
class KafkaConsumeConsoleCommand extends ConsoleCommand implements SignalableCommandInterface
{
    public function __construct(
        private readonly KafkaTopicContainer $topicContainer,
        private readonly TopicProcessor $processor,
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
            new InputArgument("topic", InputArgument::REQUIRED, "The topic to consume."),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topic = $this->topicContainer->getTopic($input->getArgument("topic"));
        $this->processor->process($topic);

        return Command::SUCCESS;
    }
}
