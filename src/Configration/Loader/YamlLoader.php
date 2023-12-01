<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Configration\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Parser;

use function in_array;
use function pathinfo;

use const PATHINFO_EXTENSION;

class YamlLoader extends FileLoader
{
    public function load($resource, $type = null): array
    {
        $yaml = new Parser();
        return $yaml->parseFile($resource);
    }

    public function supports($resource, $type = null): bool
    {
        return is_string($resource) && in_array(pathinfo($resource, PATHINFO_EXTENSION), ['yml', 'yaml']);
    }
}
