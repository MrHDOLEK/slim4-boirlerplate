<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

require_once __DIR__ . '/../vendor/autoload.php';


/** @var ContainerInterface $container */
$container = (require __DIR__ . '/../app/bootstrap.php');

$application = $container->get(Application::class);
$application->run();