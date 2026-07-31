<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Infrastructure\DependencyInjection\CompilerPass;
use App\Infrastructure\DependencyInjection\ContainerBuilder;

class TransportCompilerPass implements CompilerPass
{
    /**
     * @param array<class-string> $transportAttributes
     */
    public function __construct(
        private readonly array $transportAttributes,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(TransportContainer::class);

        foreach ($this->transportAttributes as $attribute) {
            foreach ($container->findTaggedWithClassAttribute($attribute) as $class) {
                $definition->method("registerTransport", \DI\autowire($class));
            }
        }

        $container->addDefinitions(
            [TransportContainer::class => $definition],
        );
    }
}
