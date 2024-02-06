<?php

declare(strict_types=1);

namespace Tests\Application\Console;

use App\Application\Console\Utility\CacheClearConsoleCommand;
use App\Application\Console\Utility\ConsoleCommand;
use PHPUnit\Framework\TestCase;

class ConsoleCommandTest extends TestCase
{
    public function testGetSignatureWithoutSettingAttributeSuccess(): void
    {
        $command = new class() extends ConsoleCommand {};
        $signature = $command::getSignature();

        $this->assertEmpty($signature);
    }

    public function testGetSignatureWithAttributeSuccess(): void
    {
        $signature = CacheClearConsoleCommand::getSignature();

        $this->assertEquals("app:cache:clear", $signature);
    }
}
