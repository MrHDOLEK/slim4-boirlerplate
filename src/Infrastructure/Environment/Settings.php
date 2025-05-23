<?php

declare(strict_types=1);

namespace App\Infrastructure\Environment;

use App\Infrastructure\Utils\Constants;

class Settings
{
    public function __construct(
        private readonly array $settings,
    ) {}

    public static function load(): self
    {
        return new self(require self::getAppRoot() . "/config/settings.php");
    }

    public static function getAppRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public static function getConsoleRoot(): string
    {
        return sprintf("%s%s ", dirname(__DIR__, 3), Constants::CONSOLE_ROUTE);
    }

    public function get(string $parents): mixed
    {
        $settings = $this->settings;
        $parents = explode(".", $parents);

        foreach ($parents as $parent) {
            if (is_array($settings) && (isset($settings[$parent]) || array_key_exists($parent, $settings))) {
                $settings = $settings[$parent];
            } else {
                throw new \RuntimeException(sprintf('Trying to fetch invalid setting "%s"', implode(".", $parents)));
            }
        }

        return $settings;
    }
}
