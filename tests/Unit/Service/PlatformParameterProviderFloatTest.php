<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Service\PlatformParameterProvider;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class PlatformParameterProviderFloatTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CacheItemPoolInterface $cache;
    private PlatformParameterProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->provider = new PlatformParameterProvider(
            $this->entityManager,
            $this->cache,
            PlatformParameter::class,
            3600,
            'platform_parameter'
        );
    }

    public function testGetFloatReturnsFloatValue(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('price');
        $parameter->setValue('19.99');
        $parameter->setType(ParameterType::FLOAT);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->with('platform_parameter.price')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->with(['key' => 'price'])->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getFloat('price');

        $this->assertSame(19.99, $result);
    }

    public function testGetFloatReturnsIntegerAsFloat(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('quantity');
        $parameter->setValue('42');
        $parameter->setType(ParameterType::FLOAT);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getFloat('quantity');

        $this->assertSame(42.0, $result);
    }

    public function testGetFloatHandlesNegativeValues(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('temperature');
        $parameter->setValue('-15.5');
        $parameter->setType(ParameterType::FLOAT);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getFloat('temperature');

        $this->assertSame(-15.5, $result);
    }

    public function testGetFloatHandlesScientificNotation(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('coefficient');
        $parameter->setValue('1.5e-3');
        $parameter->setType(ParameterType::FLOAT);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getFloat('coefficient');

        $this->assertSame(0.0015, $result);
    }

    public function testGetFloatThrowsExceptionForNonNumericValue(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('invalid');
        $parameter->setValue('not-a-number');
        $parameter->setType(ParameterType::FLOAT);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "invalid" value "not-a-number" is not a valid float.');

        $this->provider->getFloat('invalid');
    }

    public function testGetFloatReturnsDefaultWhenParameterNotFound(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getFloat('missing', 3.14);

        $this->assertSame(3.14, $result);
    }

    public function testGetFloatThrowsExceptionWhenNotFoundAndNoDefault(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "missing" not found.');

        $this->provider->getFloat('missing');
    }
}
