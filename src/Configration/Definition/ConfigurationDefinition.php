<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Configration\Definition;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigurationDefinition implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        $rootNode
            ->children()
                ->scalarNode('target')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('connection')
                    ->children()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->integerNode('port')->defaultValue(3306)->end()
                        ->scalarNode('user')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('dbname')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('driver')->defaultValue('pdo_mysql')->end()
                        ->scalarNode('charset')->defaultValue('utf8')->end()
                        ->scalarNode('serverVersion')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->children()
                        ->booleanNode('zip')->defaultFalse()->end()
                        ->arrayNode('schema')
                            ->children()
                                ->enumNode('method')
                                    ->defaultValue('recreate')
                                    ->values(['recreate', 'truncate', 'none'])
                                ->end()
                                ->booleanNode('skipEmptyTables')->defaultTrue()->end()
                                ->booleanNode('skipIgnoredTables')->defaultTrue()->end()
                            ->end()
                        ->end()
                        ->arrayNode('data')
                            ->children()
                                ->booleanNode('skipEmptyTables')->defaultTrue()->end()
                                ->booleanNode('skipIgnoredTables')->defaultTrue()->end()
                             ->end()
                         ->end()
                    ->end()
                ->end()
                ->arrayNode('tables')
                    ->children()
                        ->arrayNode('exclude')
                            ->ignoreExtraKeys(false)
                        ->end()
                        ->arrayNode('filter')
                            ->ignoreExtraKeys(false)
                        ->end()
                    ->end()
                ->end();
        // @formatter:on

        return $treeBuilder;
    }
}
