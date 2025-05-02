<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

class ActionLogProcessor
{
    private static ?string $currentAction = null;

    public function __invoke($record)
    {
        if (self::$currentAction === null) {
            return $record;
        }

        if (is_object($record)) {
            if (!isset($record["extra"])) {
                $record["extra"] = [];
            }
            $record["extra"]["action"] = self::$currentAction;

            return $record;
        }

        if (is_array($record)) {
            if (!isset($record["extra"])) {
                $record["extra"] = [];
            }
            $record["extra"]["action"] = self::$currentAction;
        }

        return $record;
    }

    public static function setCurrentAction(string|object $action): void
    {
        $actionName = is_object($action) ? get_class($action) : $action;

        if (str_contains($actionName, "\\")) {
            $parts = explode("\\", $actionName);
            $actionName = end($parts);
        }

        self::$currentAction = $actionName;
    }

    public static function clearCurrentAction(): void
    {
        self::$currentAction = null;
    }
}
