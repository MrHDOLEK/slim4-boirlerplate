<?php

declare(strict_types=1);

namespace App\Application\Settings;

class Settings implements SettingsInterface
{
    public function __construct(
        private array $settings,
    ) {}

    /**
     * @return mixed
     */
    public function get(string $key = "")
    {
        return (empty($key)) ? $this->settings : $this->settings[$key];
    }
}
