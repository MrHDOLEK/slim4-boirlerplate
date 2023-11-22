<?php

declare(strict_types=1);

namespace App\Application\Console;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "db:seed", description: "Command to run seeds for a databases.")]
class DataFixturesConsoleCommand extends ConsoleCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>Purge db</info>");

        $em = $this->entityManager;
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $purger->purge();

        $output->writeln("Please wait");

        $loader = new Loader();
        $loader->loadFromDirectory(dirname(__DIR__, 2) . "/Infrastructure/Persistence/Doctrine/Fixtures");
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        $output->writeln("<info>Completed load fixtures to db</info>");

        return Command::SUCCESS;
    }
}
