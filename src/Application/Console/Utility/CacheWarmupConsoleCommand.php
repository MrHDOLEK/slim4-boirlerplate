<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\Environment\Settings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "app:cache:warmup", description: "Compile the dependency injection container so it can be preloaded into opcache")]
class CacheWarmupConsoleCommand extends ConsoleCommand
{
    public function __construct(
        private readonly Settings $settings,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ContainerFactory::create();

        $compiledContainerDir = $this->settings->get("slim.cache_dir") . "/container";
        $files = is_dir($compiledContainerDir) ? glob($compiledContainerDir . "/*.php") : [];

        if ($files === false || $files === []) {
            $output->writeln("<comment>Container was not compiled. Compilation only happens when ENVIRONMENT=production.</comment>");

            return Command::SUCCESS;
        }

        $output->writeln(sprintf("<info>Container compiled: %d file(s) in %s</info>", count($files), $compiledContainerDir));

        return Command::SUCCESS;
    }
}
