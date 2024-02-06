<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use App\Infrastructure\Environment\Settings;
use GO\Scheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "schedule", description: "Command to run all cron jobs in the application.")]
class ScheduleConsoleCommand extends ConsoleCommand
{
    public function __construct(
        private readonly Scheduler $scheduler,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->scheduler->php(Settings::getConsoleRoot() . CacheClearConsoleCommand::getSignature(), PHP_BINARY)->daily();
        $this->scheduler->run();

        return Command::SUCCESS;
    }
}
