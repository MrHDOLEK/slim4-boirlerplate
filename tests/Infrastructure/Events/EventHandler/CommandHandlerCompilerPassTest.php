<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events\EventHandler;

use App\Infrastructure\Attribute\AsEventHandler;
use App\Infrastructure\DependencyInjection\ContainerBuilder;
use App\Infrastructure\Events\EventBus;
use App\Infrastructure\Events\EventHandler\EventHandler;
use App\Infrastructure\Events\EventHandler\EventHandlerCompilerPass;
use DI\Definition\Helper\AutowireDefinitionHelper;
use PHPUnit\Framework\TestCase;

class CommandHandlerCompilerPassTest extends TestCase
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
            ->with(EventBus::class)
            ->willReturn($definition);

        $containerBuilder
            ->expects($this->once())
            ->method("findTaggedWithClassAttribute")
            ->with(AsEventHandler::class)
            ->willReturn([EventHandler::class]);

        $containerBuilder
            ->expects($this->once())
            ->method("addDefinitions")
            ->with([EventBus::class => $definition]);

        $compilerPass = new EventHandlerCompilerPass();
        $compilerPass->process($containerBuilder);

        $this->assertEquals(
            [["subscribeEventHandler", [\DI\autowire(EventHandler::class)]]],
            $definition->methodCalls,
        );
    }
}
