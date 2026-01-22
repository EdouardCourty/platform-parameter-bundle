<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class PlatformParameterExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** @var string $entityClass */
        $entityClass = $config['entity_class'];
        /** @var int $cacheTtl */
        $cacheTtl = $config['cache_ttl'];
        /** @var string $cacheKeyPrefix */
        $cacheKeyPrefix = $config['cache_key_prefix'];
        /** @var bool $clearCacheOnUpdate */
        $clearCacheOnUpdate = $config['clear_cache_on_parameter_update'];
        /** @var string|null $cacheAdapter */
        $cacheAdapter = $config['cache_adapter'];

        $container->setParameter('platform_parameter.entity_class', $entityClass);
        $container->setParameter('platform_parameter.cache_ttl', $cacheTtl);
        $container->setParameter('platform_parameter.cache_key_prefix', $cacheKeyPrefix);
        $container->setParameter('platform_parameter.clear_cache_on_parameter_update', $clearCacheOnUpdate);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Create default TagAware cache pool if not already defined
        $this->registerDefaultCachePool($container);

        // Resolve cache adapter with fallback logic
        $resolvedCacheAdapter = $this->resolveCacheAdapter($container, $cacheAdapter);

        // Conditionally register the cache clearing listener
        if ($clearCacheOnUpdate) {
            $this->registerCacheClearingListener($container, $cacheKeyPrefix, $resolvedCacheAdapter);
        }
    }

    /**
     * Register a default TagAware cache pool for optimal performance.
     */
    private function registerDefaultCachePool(ContainerBuilder $container): void
    {
        // Only create if not already defined by the user
        if ($container->hasDefinition('platform_parameter.cache')) {
            return;
        }

        // Create a TagAware cache adapter wrapping the default app cache
        // TagAwareAdapter constructor signature: __construct(AdapterInterface $itemsPool, TagAwareAdapterInterface $tagsPool = null)
        $container->register('platform_parameter.cache', 'Symfony\Component\Cache\Adapter\TagAwareAdapter')
            ->setArguments([
                new Reference('cache.app'), // itemsPool
                null, // tagsPool (null = same as itemsPool)
            ]);
    }

    /**
     * Resolve cache adapter with fallback logic:
     * 1. Use custom cache_adapter if provided
     * 2. Else use platform_parameter.cache (auto-created TagAware adapter)
     * 3. Else use cache.app as fallback
     */
    private function resolveCacheAdapter(ContainerBuilder $container, ?string $cacheAdapter): string
    {
        // 1. If custom adapter is specified in config
        if ($cacheAdapter !== null) {
            if (!$container->has($cacheAdapter)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The cache adapter service "%s" specified in platform_parameter.cache_adapter does not exist. '.
                        'Make sure the service is defined in your container.',
                        $cacheAdapter
                    )
                );
            }
            return $cacheAdapter;
        }

        // 2. Use platform_parameter.cache (should always exist now due to registerDefaultCachePool)
        if ($container->has('platform_parameter.cache') || $container->hasDefinition('platform_parameter.cache')) {
            return 'platform_parameter.cache';
        }

        // 3. Fallback to cache.app (should rarely happen)
        return 'cache.app';
    }

    private function registerCacheClearingListener(ContainerBuilder $container, string $cacheKeyPrefix, string $cacheAdapter): void
    {
        $container->register('platform_parameter.listener', 'Ecourty\PlatformParameterBundle\EventListener\PlatformParameterListener')
            ->setArguments([
                new Reference($cacheAdapter),
                $cacheKeyPrefix,
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postPersist',
                'entity' => 'Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter',
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postUpdate',
                'entity' => 'Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter',
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postRemove',
                'entity' => 'Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter',
            ]);
    }
}
