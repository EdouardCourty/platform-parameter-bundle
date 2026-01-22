<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\EventListener;

use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Doctrine entity listener that automatically clears parameter cache on entity changes.
 */
class PlatformParameterListener
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $cacheKeyPrefix,
    ) {
    }

    public function postPersist(AbstractPlatformParameter $entity): void
    {
        $this->clearParameterCache($entity);
    }

    public function postUpdate(AbstractPlatformParameter $entity): void
    {
        $this->clearParameterCache($entity);
    }

    public function postRemove(AbstractPlatformParameter $entity): void
    {
        $this->clearParameterCache($entity);
    }

    private function clearParameterCache(AbstractPlatformParameter $entity): void
    {
        $cacheKey = sprintf('%s.%s', $this->cacheKeyPrefix, $entity->getKey());
        $this->cache->deleteItem($cacheKey);
    }
}
