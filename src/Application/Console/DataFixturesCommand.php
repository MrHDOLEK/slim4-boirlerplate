<?php

declare(strict_types=1);

namespace App\Application\Console;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataFixturesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName("db:seed");
        $this->setDescription("Command to run seeds for a databases.");
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
