<?php

declare(strict_types=1);
use DI\CompiledContainer;
use DI\Container;

if (!function_exists("opcache_compile_file") || !ini_get("opcache.enable")) {
    return;
}

$appRoot = dirname(__DIR__);

$autoload = $appRoot . "/vendor/autoload.php";

if (!is_file($autoload)) {
    return;
}

require_once $autoload;

class_exists(Container::class);
class_exists(CompiledContainer::class);

$compiledContainerDir = $appRoot . "/var/cache/slim/container";

if (!is_dir($compiledContainerDir)) {
    return;
}

$files = glob($compiledContainerDir . "/*.php");

if ($files === false) {
    return;
}

foreach ($files as $file) {
    require_once $file;
}
