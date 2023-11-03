<?php

declare(strict_types=1);

namespace App\Infrastructure\Events\EventHandler;

use App\Infrastructure\Attribute\AsEventHandler;
use App\Infrastructure\DependencyInjection\CompilerPass;
use App\Infrastructure\DependencyInjection\ContainerBuilder;
use App\Infrastructure\Events\EventBus;

class EventHandlerCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(EventBus::class);

        foreach ($container->findTaggedWithClassAttribute(AsEventHandler::class) as $class) {
            $definition->method("subscribeEventHandler", \DI\autowire($class));
        }

        $container->addDefinitions(
            [EventBus::class => $definition],
        );
    }
}
