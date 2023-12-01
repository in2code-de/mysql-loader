<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use CoStack\MysqlLoader\Configration\Definition\ConfigurationDefinition;
use CoStack\MysqlLoader\Configration\Loader\YamlLoader;
use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Config\ConfigConfig;

class ConfigurationProcessor
{
    public function loadFile(string $file): array
    {
        $loader = new YamlLoader(new FileLocator());
        $contents = $loader->import($file);

        $processor = new Processor();
        $configuration = new ConfigurationDefinition();
        $tre = $configuration->getConfigTreeBuilder()->buildTree();
        return $processor->process($tre, [$contents]);
    }
}
