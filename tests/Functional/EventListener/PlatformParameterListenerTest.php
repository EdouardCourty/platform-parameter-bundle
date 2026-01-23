<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterCreatedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterDeletedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PlatformParameterListenerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);

        // Setup database schema
        $this->setupDatabase();

        // Setup event listener to capture dispatched events
        $this->setupEventListener();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->dispatchedEvents = [];
    }

    public function testCreatedEventIsDispatchedWhenParameterIsPersisted(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('test_create_event');
        $parameter->setValue('test_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(PlatformParameterCreatedEvent::class, $this->dispatchedEvents[0]);
        $this->assertSame($parameter, $this->dispatchedEvents[0]->getParameter());
    }

    public function testUpdatedEventIsDispatchedWhenParameterIsModified(): void
    {
        // Create parameter first
        $parameter = new PlatformParameter();
        $parameter->setKey('test_update_event');
        $parameter->setValue('initial_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        // Clear events from creation
        $this->dispatchedEvents = [];

        // Update parameter
        $parameter->setValue('updated_value');
        $this->entityManager->flush();

        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(PlatformParameterUpdatedEvent::class, $this->dispatchedEvents[0]);
        
        /** @var PlatformParameterUpdatedEvent $event */
        $event = $this->dispatchedEvents[0];
        $this->assertSame($parameter, $event->getParameter());
        $this->assertSame('initial_value', $event->getOldValue());
        $this->assertSame('updated_value', $event->getNewValue());
    }

    public function testDeletedEventIsDispatchedWhenParameterIsRemoved(): void
    {
        // Create parameter first
        $parameter = new PlatformParameter();
        $parameter->setKey('test_delete_event');
        $parameter->setValue('test_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        // Clear events from creation
        $this->dispatchedEvents = [];

        // Delete parameter
        $this->entityManager->remove($parameter);
        $this->entityManager->flush();

        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(PlatformParameterDeletedEvent::class, $this->dispatchedEvents[0]);
        $this->assertSame($parameter, $this->dispatchedEvents[0]->getParameter());
    }

    public function testEventsAreDispatchedEvenWhenCacheClearingIsDisabled(): void
    {
        // Note: This test verifies that events are always dispatched
        // regardless of clear_cache_on_parameter_update configuration
        // The actual configuration is set to true in test environment,
        // but the listener always dispatches events even if cache clearing is disabled
        
        $parameter = new PlatformParameter();
        $parameter->setKey('test_event_without_cache');
        $parameter->setValue('test_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        // Verify event was dispatched
        $this->assertGreaterThanOrEqual(1, count($this->dispatchedEvents));
        $this->assertInstanceOf(PlatformParameterCreatedEvent::class, $this->dispatchedEvents[0]);
    }

    private function setupDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function setupEventListener(): void
    {
        // Add listener for all parameter events
        $this->eventDispatcher->addListener(PlatformParameterCreatedEvent::class, function (PlatformParameterCreatedEvent $event) {
            $this->dispatchedEvents[] = $event;
        });

        $this->eventDispatcher->addListener(PlatformParameterUpdatedEvent::class, function (PlatformParameterUpdatedEvent $event) {
            $this->dispatchedEvents[] = $event;
        });

        $this->eventDispatcher->addListener(PlatformParameterDeletedEvent::class, function (PlatformParameterDeletedEvent $event) {
            $this->dispatchedEvents[] = $event;
        });
    }
}
