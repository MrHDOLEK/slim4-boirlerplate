<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Console;

use App\Infrastructure\Console\ConsoleCommandCompilerPass;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\DependencyInjection\ContainerBuilder;
use DI\Definition\Helper\AutowireDefinitionHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

class ConsoleCommandCompilerPassTest extends TestCase
{
    public function testProcessSuccess(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $definition = new class() extends AutowireDefinitionHelper {
            public array $methodCalls = [];

            public function method(string $method, mixed ...$parameters): self
            {
                $this->methodCalls[] = [$method, $parameters];

                return $this;
            }
        };

        $containerBuilder
            ->expects($this->once())
            ->method("findDefinition")
            ->with(ConsoleCommandContainer::class)
            ->willReturn($definition);

        $containerBuilder
            ->expects($this->once())
            ->method("findTaggedWithClassAttribute")
            ->with(AsCommand::class)
            ->willReturn([Command::class]);

        $containerBuilder
            ->expects($this->once())
            ->method("addDefinitions")
            ->with([ConsoleCommandContainer::class => $definition]);

        $compilerPass = new ConsoleCommandCompilerPass();
        $compilerPass->process($containerBuilder);

        $this->assertEquals(
            [["registerCommand", [\DI\autowire(Command::class)]]],
            $definition->methodCalls,
        );
    }
}
