<?php

declare(strict_types=1);

namespace App\Infrastructure\Utils;

class Constants
{
    public const ONE_HOUR = 1 * 60 * 60;
    public const CONSOLE_ROUTE = "/bin/console.php";
    public const ARGUMENT_SEPARATOR = "_";
    public const MAX_RETRY_COUNT = 3;
}
