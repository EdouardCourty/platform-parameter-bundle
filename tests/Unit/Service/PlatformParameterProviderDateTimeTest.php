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

final class PlatformParameterProviderDateTimeTest extends TestCase
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

    public function testGetDatetimeWithIso8601Format(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('event_date');
        $parameter->setValue('2026-01-21T15:30:00+00:00');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('event_date');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-21', $result->format('Y-m-d'));
        $this->assertSame('15:30:00', $result->format('H:i:s'));
    }

    public function testGetDatetimeWithDateOnly(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('birth_date');
        $parameter->setValue('1990-05-15');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('birth_date');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('1990-05-15', $result->format('Y-m-d'));
    }

    public function testGetDatetimeWithMysqlDatetimeFormat(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('created_at');
        $parameter->setValue('2026-01-21 15:30:00');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('created_at');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-21 15:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function testGetDatetimeWithFrenchFormat(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('french_date');
        $parameter->setValue('21/01/2026');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('french_date');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('21/01/2026', $result->format('d/m/Y'));
    }

    public function testGetDatetimeWithUnixTimestamp(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('timestamp');
        $parameter->setValue('1737472200'); // 2026-01-21 15:30:00 UTC
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('timestamp');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('1737472200', $result->format('U'));
    }

    public function testGetDatetimeWithCustomFormat(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('custom_date');
        $parameter->setValue('15-05-1990');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('custom_date', null, 'd-m-Y');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('15-05-1990', $result->format('d-m-Y'));
    }

    public function testGetDatetimeWithNativeParser(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('relative_date');
        $parameter->setValue('next monday');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with($parameter);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->provider->getDateTime('relative_date');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        // Can't assert specific date since it's relative
    }

    public function testGetDatetimeThrowsExceptionForInvalidValue(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('invalid_date');
        $parameter->setValue('not-a-date');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "invalid_date" value "not-a-date" cannot be parsed as datetime.');

        $this->provider->getDateTime('invalid_date');
    }

    public function testGetDatetimeThrowsExceptionForInvalidCustomFormat(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('date');
        $parameter->setValue('2026-01-21');
        $parameter->setType(ParameterType::DATETIME);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($parameter);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "date" value "2026-01-21" cannot be parsed with format "d/m/Y".');

        $this->provider->getDateTime('date', null, 'd/m/Y');
    }

    public function testGetDatetimeReturnsDefaultWhenParameterNotFound(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $default = new \DateTimeImmutable('2025-01-01');
        $result = $this->provider->getDateTime('missing', $default);

        $this->assertSame($default, $result);
    }

    public function testGetDatetimeThrowsExceptionWhenNotFoundAndNoDefault(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "missing" not found.');

        $this->provider->getDateTime('missing');
    }
}
