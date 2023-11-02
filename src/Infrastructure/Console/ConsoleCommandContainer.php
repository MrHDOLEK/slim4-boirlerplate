<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;

class ConsoleCommandContainer
{
    /** @var array<Command> */
    private array $consoleCommands = [];

    public function registerCommand(Command $command): void
    {
        if (empty($command->getName())) {
            throw new RuntimeException("Command name cannot be empty");
        }

        if (array_key_exists($command->getName(), $this->getCommands())) {
            throw new RuntimeException(sprintf('Command "%s" already registered in container', $command->getName()));
        }
        $this->consoleCommands[$command->getName()] = $command;
    }

    /**
     * @return array<Command>
     */
    public function getCommands(): array
    {
        return $this->consoleCommands;
    }
}
