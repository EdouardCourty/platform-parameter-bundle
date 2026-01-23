<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\DependencyInjection;

use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;
use Ecourty\PlatformParameterBundle\EventListener\PlatformParameterListener;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class PlatformParameterExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // Get bundle configuration to determine entity class
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** @var string $entityClass */
        $entityClass = $config['entity_class'];

        // Validate entity class exists
        if (!\class_exists($entityClass)) {
            throw new \InvalidArgumentException(\sprintf('Entity class "%s" does not exist', $entityClass));
        }

        // Auto-configure Doctrine ORM mapping based on entity_class
        /* @var class-string $entityClass */
        $this->configureDoctrine($container, $entityClass);
    }

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

        // Always register the listener (for events), pass clearCacheOnUpdate flag
        $this->registerParameterListener($container, $cacheKeyPrefix, $resolvedCacheAdapter, $clearCacheOnUpdate);
    }

    /**
     * Configure Doctrine ORM mapping for the entity class.
     *
     * @param class-string $entityClass
     */
    private function configureDoctrine(ContainerBuilder $container, string $entityClass): void
    {
        // Determine the directory and namespace based on entity class
        $reflectionClass = new \ReflectionClass($entityClass);
        $fileName = $reflectionClass->getFileName();

        if (false === $fileName) {
            throw new \RuntimeException(\sprintf('Cannot determine file path for entity class "%s"', $entityClass));
        }

        $entityDir = \dirname($fileName);
        $entityNamespace = $reflectionClass->getNamespaceName();

        // Configure Doctrine mapping
        $doctrineConfig = [
            'mappings' => [
                'PlatformParameterBundle' => [
                    'type' => 'attribute',
                    'dir' => $entityDir,
                    'prefix' => $entityNamespace,
                    'alias' => 'PlatformParameterBundle',
                    'is_bundle' => false,
                ],
            ],
        ];

        $container->prependExtensionConfig('doctrine', ['orm' => $doctrineConfig]);
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
        $container->register('platform_parameter.cache', TagAwareAdapter::class)
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
        if (null !== $cacheAdapter) {
            if (!$container->has($cacheAdapter)) {
                throw new \InvalidArgumentException(\sprintf('The cache adapter service "%s" specified in platform_parameter.cache_adapter does not exist. Make sure the service is defined in your container.', $cacheAdapter));
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

    /**
     * Register the parameter listener.
     * Always registered for event dispatching, cache clearing is optional based on configuration.
     */
    private function registerParameterListener(ContainerBuilder $container, string $cacheKeyPrefix, string $cacheAdapter, bool $clearCacheEnabled): void
    {
        $container->register('platform_parameter.listener', PlatformParameterListener::class)
            ->setArguments([
                new Reference($cacheAdapter),
                $cacheKeyPrefix,
                new Reference('event_dispatcher'),
                $clearCacheEnabled,
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postPersist',
                'entity' => AbstractPlatformParameter::class,
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postUpdate',
                'entity' => AbstractPlatformParameter::class,
            ])
            ->addTag('doctrine.orm.entity_listener', [
                'event' => 'postRemove',
                'entity' => AbstractPlatformParameter::class,
            ]);
    }
}
