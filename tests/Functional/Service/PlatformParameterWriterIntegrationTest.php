<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Exception\ParameterTypeMismatchException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PlatformParameterWriterIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PlatformParameterProviderInterface $provider;
    private PlatformParameterWriterInterface $writer;
    private CacheItemPoolInterface $cache;
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->provider = static::getContainer()->get(PlatformParameterProviderInterface::class);
        $this->writer = static::getContainer()->get(PlatformParameterWriterInterface::class);
        $this->cache = static::getContainer()->get('platform_parameter.cache');

        $this->setupDatabase();
        $this->setupEventListener();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->dispatchedEvents = [];
    }

    public function testWriteThenReadCycle(): void
    {
        $parameter = $this->createParameter('test_key', '100', ParameterType::INTEGER);
        $this->writer->setInt('test_key', 200);
        $value = $this->provider->getInt('test_key');
        $this->assertSame(200, $value);
    }

    public function testWriteAutomaticallyClearsCache(): void
    {
        $parameter = $this->createParameter('cache_test', 'initial', ParameterType::STRING);
        $value = $this->provider->getString('cache_test');
        $this->assertSame('initial', $value);
        $this->writer->setString('cache_test', 'updated');
        $value = $this->provider->getString('cache_test');
        $this->assertSame('updated', $value);
    }

    public function testWriteDispatchesUpdateEvent(): void
    {
        $parameter = $this->createParameter('event_test', 'old_value', ParameterType::STRING);
        $this->dispatchedEvents = [];
        $this->writer->setString('event_test', 'new_value');
        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(PlatformParameterUpdatedEvent::class, $this->dispatchedEvents[0]);
        $event = $this->dispatchedEvents[0];
        $this->assertSame('old_value', $event->getOldValue());
        $this->assertSame('new_value', $event->getNewValue());
    }

    public function testSetAllTypes(): void
    {
        $this->createParameter('string_param', 'initial', ParameterType::STRING);
        $this->createParameter('int_param', '10', ParameterType::INTEGER);
        $this->createParameter('bool_param', '0', ParameterType::BOOLEAN);
        $this->createParameter('json_param', '{"key":"value"}', ParameterType::JSON);
        $this->createParameter('list_param', "a\nb", ParameterType::LIST);
        $this->createParameter('float_param', '3.14', ParameterType::FLOAT);

        $this->writer->setString('string_param', 'updated');
        $this->writer->setInt('int_param', 42);
        $this->writer->setBool('bool_param', true);
        $this->writer->setJson('json_param', ['new' => 'data']);
        $this->writer->setList('list_param', ['x', 'y', 'z']);
        $this->writer->setFloat('float_param', 2.718);

        $this->assertSame('updated', $this->provider->getString('string_param'));
        $this->assertSame(42, $this->provider->getInt('int_param'));
        $this->assertTrue($this->provider->getBool('bool_param'));
        $this->assertSame(['new' => 'data'], $this->provider->getJson('json_param'));
        $this->assertSame(['x', 'y', 'z'], $this->provider->getList('list_param'));
        $this->assertEqualsWithDelta(2.718, $this->provider->getFloat('float_param'), 0.001);
    }

    public function testSetThrowsExceptionWhenParameterNotFound(): void
    {
        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "missing_key" not found.');
        $this->writer->setString('missing_key', 'value');
    }

    public function testSetThrowsExceptionWhenTypeMismatch(): void
    {
        $this->createParameter('string_param', 'value', ParameterType::STRING);
        $this->expectException(ParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Cannot set parameter "string_param" with type integer: parameter type is string.');
        $this->writer->setInt('string_param', 42);
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
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(PlatformParameterUpdatedEvent::class, function ($event) {
            $this->dispatchedEvents[] = $event;
        });
    }

    private function createParameter(string $key, string $value, ParameterType $type): PlatformParameter
    {
        $parameter = new PlatformParameter();
        $parameter->setKey($key);
        $parameter->setValue($value);
        $parameter->setType($type);
        $parameter->setLabel('Test Label');
        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        return $parameter;
    }
}
