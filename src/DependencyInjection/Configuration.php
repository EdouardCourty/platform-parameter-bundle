<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('platform_parameter');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('entity_class')
                    ->info('FQCN of the entity class to use (must extend AbstractPlatformParameter)')
                    ->defaultValue('Ecourty\PlatformParameterBundle\Entity\PlatformParameter')
                    ->validate()
                        ->ifTrue(fn ($v) => !\is_string($v) || !\class_exists($v))
                        ->thenInvalid('The entity class "%s" does not exist.')
                    ->end()
                    ->validate()
                        ->ifTrue(fn ($v) => !\is_string($v) || !\is_subclass_of($v, 'Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter'))
                        ->thenInvalid('The entity class "%s" must extend AbstractPlatformParameter.')
                    ->end()
                ->end()
                ->integerNode('cache_ttl')
                    ->info('Cache TTL in seconds for platform parameters')
                    ->defaultValue(3600)
                    ->min(0)
                ->end()
                ->scalarNode('cache_key_prefix')
                    ->info('Prefix for cache keys')
                    ->defaultValue('platform_parameter')
                ->end()
                ->booleanNode('clear_cache_on_parameter_update')
                    ->info('Automatically clear parameter cache when a parameter is created, updated, or deleted')
                    ->defaultTrue()
                ->end()
                ->scalarNode('cache_adapter')
                    ->info('Cache adapter service ID to use (default: platform_parameter.cache if exists, otherwise cache.app)')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
