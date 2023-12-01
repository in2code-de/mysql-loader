#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__ . '/../../vendor/autoload.php';

$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
$loader->load('services.yaml');
$loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
$loader->load('services.php');
$container->compile();
$dumper = new PhpDumper($container);
$content = $dumper->dump([
    'class' => 'DI',
    'namespace' => 'CoStack\\MysqlLoader\\Generated',
    'debug' => false,
]);
$cache = new ConfigCache(__DIR__ . '/../../src/Generated/DI.php', false);
$cache->write($content);
