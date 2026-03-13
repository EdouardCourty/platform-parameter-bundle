<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\DependencyInjection;

use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\EventListener\PlatformParameterListener;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class PlatformParameterExtension extends Extension implements PrependExtensionInterface
{
    private const string DEFAULT_CACHE_POOL_SERVICE_ID = 'platform_parameter.cache';
    private const string DEFAULT_CACHE_POOL_INNER_SERVICE_ID = 'platform_parameter.cache.inner';

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

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        // Create default TagAware cache pool
        $this->registerDefaultCachePool($container, $cacheTtl);

        // Resolve cache adapter with fallback logic
        $resolvedCacheAdapter = $this->resolveCacheAdapter($container, $cacheAdapter);

        // Configure PlatformParameterProvider with explicit cache injection
        $this->configurePlatformParameterProvider($container, $entityClass, $resolvedCacheAdapter, $cacheTtl, $cacheKeyPrefix);

        // Configure PlatformParameterWriter with entity class
        $this->configurePlatformParameterWriter($container, $entityClass);

        // Always register the listener (for events), pass clearCacheOnUpdate flag
        $this->registerParameterListener($container, $cacheKeyPrefix, $resolvedCacheAdapter, $clearCacheOnUpdate);

        // Conditionally register Twig extension if Twig is installed
        if (\class_exists(\Twig\Extension\AbstractExtension::class)) {
            $loader->load('services_twig.php');
        }
    }

    /**
     * Configure Doctrine ORM mapping for the entity class.
     *
     * @param class-string $entityClass
     */
    private function configureDoctrine(ContainerBuilder $container, string $entityClass): void
    {
        $defaultEntityClass = PlatformParameter::class;

        // Always map Model/ directory so AbstractPlatformParameter (MappedSuperclass) is discoverable
        $bundleModelDir = \dirname(__DIR__).'/Model';
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'PlatformParameterBundleModel' => [
                        'type' => 'attribute',
                        'dir' => $bundleModelDir,
                        'prefix' => 'Ecourty\PlatformParameterBundle\Model',
                        'alias' => 'PlatformParameterBundleModel',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);

        // If using custom entity, map only the custom entity directory
        if ($entityClass !== $defaultEntityClass) {
            $reflectionClass = new \ReflectionClass($entityClass);
            $fileName = $reflectionClass->getFileName();

            if (false === $fileName) {
                throw new \RuntimeException(\sprintf('Cannot determine file path for entity class "%s"', $entityClass));
            }

            $entityDir = \dirname($fileName);
            $entityNamespace = $reflectionClass->getNamespaceName();

            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'PlatformParameterBundle' => [
                            'type' => 'attribute',
                            'dir' => $entityDir,
                            'prefix' => $entityNamespace,
                            'alias' => 'PlatformParameterBundle',
                            'is_bundle' => false,
                        ],
                    ],
                ],
            ]);

            return;
        }

        // Using default entity: map the bundle's Entity directory
        $bundleEntityDir = \dirname(__DIR__).'/Entity';

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'PlatformParameterBundle' => [
                        'type' => 'attribute',
                        'dir' => $bundleEntityDir,
                        'prefix' => 'Ecourty\PlatformParameterBundle\Entity',
                        'alias' => 'PlatformParameterBundle',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Register default TagAware cache pool for optimal performance.
     * Creates a filesystem-based TagAwareAdapter if not already defined by the user.
     */
    private function registerDefaultCachePool(ContainerBuilder $container, int $cacheTtl): void
    {
        // Only create if not already defined by the user
        if ($container->hasDefinition(self::DEFAULT_CACHE_POOL_SERVICE_ID)) {
            return;
        }

        // Create inner FilesystemAdapter
        $innerDefinition = new Definition(FilesystemAdapter::class);
        $innerDefinition->setArguments([
            'platform_parameter',  // namespace
            $cacheTtl,            // default lifetime
            '%kernel.cache_dir%/pools/platform_parameter', // directory
        ]);
        $innerDefinition->setPublic(false);
        $container->setDefinition(self::DEFAULT_CACHE_POOL_INNER_SERVICE_ID, $innerDefinition);

        // Create TagAwareAdapter wrapper
        $cacheDefinition = new Definition(TagAwareAdapter::class);
        $cacheDefinition->setArguments([
            new Reference(self::DEFAULT_CACHE_POOL_INNER_SERVICE_ID),
        ]);
        $cacheDefinition->setPublic(false);
        $container->setDefinition(self::DEFAULT_CACHE_POOL_SERVICE_ID, $cacheDefinition);
    }

    /**
     * Configure PlatformParameterProvider with explicit cache injection.
     * This ensures the correct cache adapter is used instead of relying on autowiring.
     */
    private function configurePlatformParameterProvider(
        ContainerBuilder $container,
        string $entityClass,
        string $cacheAdapter,
        int $cacheTtl,
        string $cacheKeyPrefix,
    ): void {
        $definition = $container->getDefinition(PlatformParameterProviderInterface::class);
        $definition->setArgument('$cache', new Reference($cacheAdapter));
        $definition->setArgument('$entityClass', $entityClass);
        $definition->setArgument('$cacheTtl', $cacheTtl);
        $definition->setArgument('$cacheKeyPrefix', $cacheKeyPrefix);
    }

    /**
     * Configure PlatformParameterWriter with entity class.
     */
    private function configurePlatformParameterWriter(
        ContainerBuilder $container,
        string $entityClass,
    ): void {
        $definition = $container->getDefinition(PlatformParameterWriterInterface::class);
        $definition->setArgument('$entityClass', $entityClass);
    }

    /**
     * Resolve cache adapter with fallback logic:
     * 1. Use custom cache_adapter if provided
     * 2. Else use platform_parameter.cache (auto-created TagAware adapter)
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

        // 2. Use platform_parameter.cache (created by registerDefaultCachePool)
        return self::DEFAULT_CACHE_POOL_SERVICE_ID;
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
