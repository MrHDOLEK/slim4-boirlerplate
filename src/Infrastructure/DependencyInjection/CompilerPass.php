<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

interface CompilerPass
{
    public function process(ContainerBuilder $container): void;
}
