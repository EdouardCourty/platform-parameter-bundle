<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\EventListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterCreatedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterDeletedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Doctrine entity listener that automatically clears parameter cache on entity changes
 * and dispatches Symfony events.
 */
class PlatformParameterListener
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $cacheKeyPrefix,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly bool $clearCacheEnabled = true,
    ) {
    }

    public function postPersist(AbstractPlatformParameter $entity, PostPersistEventArgs $args): void
    {
        if ($this->clearCacheEnabled) {
            $this->clearParameterCache($entity);
        }
        
        $this->eventDispatcher->dispatch(new PlatformParameterCreatedEvent($entity));
    }

    public function postUpdate(AbstractPlatformParameter $entity, PostUpdateEventArgs $args): void
    {
        if ($this->clearCacheEnabled) {
            $this->clearParameterCache($entity);
        }
        
        // Get old and new values from UnitOfWork change set
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        
        if (isset($changeSet['value']) && \is_array($changeSet['value'])) {
            $oldValue = $changeSet['value'][0];
            $newValue = $changeSet['value'][1];
            \assert(\is_string($oldValue));
            \assert(\is_string($newValue));
        } else {
            // No change to value field, use current value for both
            $oldValue = $newValue = $entity->getValue();
        }
        
        $this->eventDispatcher->dispatch(new PlatformParameterUpdatedEvent(
            $entity,
            $oldValue,
            $newValue
        ));
    }

    public function postRemove(AbstractPlatformParameter $entity, PostRemoveEventArgs $args): void
    {
        if ($this->clearCacheEnabled) {
            $this->clearParameterCache($entity);
        }
        
        $this->eventDispatcher->dispatch(new PlatformParameterDeletedEvent($entity));
    }

    private function clearParameterCache(AbstractPlatformParameter $entity): void
    {
        $cacheKey = \sprintf('%s.%s', $this->cacheKeyPrefix, $entity->getKey());
        $this->cache->deleteItem($cacheKey);
    }
}
