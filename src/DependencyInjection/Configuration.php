<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\DependencyInjection;

use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    private const int DEFAULT_CACHE_TTL = 3600;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('platform_parameter');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('entity_class')
                    ->info('FQCN of the entity class to use (must extend AbstractPlatformParameter)')
                    ->defaultValue(PlatformParameter::class)
                    ->validate()
                        ->ifTrue(static fn ($v) => !\is_string($v) || !\class_exists($v))
                        ->thenInvalid('The entity class "%s" does not exist.')
                    ->end()
                    ->validate()
                        ->ifTrue(static fn ($v) => !\is_string($v) || !\is_subclass_of($v, AbstractPlatformParameter::class))
                        ->thenInvalid('The entity class "%s" must extend AbstractPlatformParameter.')
                    ->end()
                ->end()
                ->integerNode('cache_ttl')
                    ->info('Cache TTL in seconds for platform parameters')
                    ->defaultValue(self::DEFAULT_CACHE_TTL)
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
                    ->info('Cache adapter service ID to use. Default: "platform_parameter.cache" (auto-created tag-aware pool using cache.adapter.filesystem.tag_aware). You can override with your own tag-aware pool (e.g., Redis).')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
