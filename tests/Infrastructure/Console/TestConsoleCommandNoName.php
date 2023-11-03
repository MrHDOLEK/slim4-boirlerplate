<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: "", description: "Test command")]
class TestConsoleCommandNoName extends Command
{
}
