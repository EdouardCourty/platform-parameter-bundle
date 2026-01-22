<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Service\PlatformParameterProvider;
use Ecourty\PlatformParameterBundle\Tests\Fixtures\CustomPlatformParameter;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CustomEntityProviderTest extends TestCase
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
            CustomPlatformParameter::class,
            3600,
            'platform_parameter'
        );
    }

    public function testProviderWorksWithCustomEntity(): void
    {
        $customParameter = new CustomPlatformParameter();
        $customParameter->setKey('site_name');
        $customParameter->setValue('My Custom Site');
        $customParameter->setType(ParameterType::STRING);
        $customParameter->setLabel('Site Name');
        $customParameter->setCategory('general');
        $customParameter->setSortOrder(10);
        $customParameter->setIcon('globe');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')
            ->with(['key' => 'site_name'])
            ->willReturn($customParameter);

        $this->entityManager->method('getRepository')
            ->with(CustomPlatformParameter::class)
            ->willReturn($repository);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('set')->willReturnSelf();
        $cacheItem->method('expiresAfter')->willReturnSelf();

        $this->cache->method('getItem')
            ->willReturn($cacheItem);
        $this->cache->method('save')
            ->willReturn(true);

        $result = $this->provider->getString('site_name');

        $this->assertSame('My Custom Site', $result);
    }

    public function testCustomEntityFieldsAreAccessible(): void
    {
        $customParameter = new CustomPlatformParameter();
        $customParameter->setKey('max_upload');
        $customParameter->setValue('100');
        $customParameter->setType(ParameterType::INTEGER);
        $customParameter->setLabel('Max Upload Size');
        $customParameter->setCategory('uploads');
        $customParameter->setSortOrder(5);
        $customParameter->setIcon('upload-cloud');

        $this->assertSame('uploads', $customParameter->getCategory());
        $this->assertSame(5, $customParameter->getSortOrder());
        $this->assertSame('upload-cloud', $customParameter->getIcon());
    }

    public function testCustomEntityExtendsAbstractParameter(): void
    {
        $customParameter = new CustomPlatformParameter();
        $customParameter->setKey('test_key');
        $customParameter->setValue('test_value');
        $customParameter->setType(ParameterType::STRING);
        $customParameter->setLabel('Test Label');

        $this->assertSame('test_key', $customParameter->getKey());
        $this->assertSame('test_value', $customParameter->getValue());
        $this->assertSame(ParameterType::STRING, $customParameter->getType());
        $this->assertSame('Test Label', $customParameter->getLabel());
    }
}
