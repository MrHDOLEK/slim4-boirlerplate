<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic;

use App\Infrastructure\Attribute\AsKafkaTopic;
use App\Infrastructure\DependencyInjection\CompilerPass;
use App\Infrastructure\DependencyInjection\ContainerBuilder;

class KafkaTopicCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(KafkaTopicContainer::class);

        foreach ($container->findTaggedWithClassAttribute(AsKafkaTopic::class) as $class) {
            $definition->method("registerTopic", \DI\autowire($class));
        }

        $container->addDefinitions(
            [KafkaTopicContainer::class => $definition],
        );
    }
}
