<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

class ConsoleCommand extends Command
{
    /**
     * @var string The signature of the console command, automatically set
     *             from the AsCommand attribute.
     */
    protected static string $signature = "";

    public static function getSignature(): string
    {
        if (empty(static::$signature)) {
            static::initializeCommandName();
        }

        return static::$signature;
    }

    protected static function initializeCommandName(): void
    {
        $reflectionClass = new ReflectionClass(static::class);
        $attributes = $reflectionClass->getAttributes(AsCommand::class);

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            static::$signature = $instance->name;
        }
    }
}
