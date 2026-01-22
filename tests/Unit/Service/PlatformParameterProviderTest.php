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

class PlatformParameterProviderTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CacheItemPoolInterface $cache;
    private EntityRepository $repository;
    private PlatformParameterProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(PlatformParameter::class)
            ->willReturn($this->repository);

        $this->provider = new PlatformParameterProvider(
            $this->entityManager,
            $this->cache,
            PlatformParameter::class,
            3600,
            'test_param'
        );
    }

    public function testGetStringReturnsValue(): void
    {
        $parameter = $this->createParameter('key', '  test value  ', ParameterType::STRING);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getString('key');

        $this->assertSame('test value', $result);
    }

    public function testGetStringReturnsDefaultWhenNotFound(): void
    {
        $this->mockCacheAndRepository('key', null);

        $result = $this->provider->getString('key', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetStringThrowsExceptionWhenNotFoundAndNoDefault(): void
    {
        $this->mockCacheAndRepository('key', null);

        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "key" not found.');

        $this->provider->getString('key');
    }

    public function testGetIntReturnsInteger(): void
    {
        $parameter = $this->createParameter('key', '42', ParameterType::INTEGER);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getInt('key');

        $this->assertSame(42, $result);
    }

    public function testGetIntReturnsDefaultWhenNotFound(): void
    {
        $this->mockCacheAndRepository('key', null);

        $result = $this->provider->getInt('key', 10);

        $this->assertSame(10, $result);
    }

    public function testGetIntThrowsExceptionForInvalidValue(): void
    {
        $parameter = $this->createParameter('key', 'not_a_number', ParameterType::INTEGER);
        $this->mockCacheAndRepository('key', $parameter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "key" value "not_a_number" is not a valid integer.');

        $this->provider->getInt('key');
    }

    public function testGetBoolReturnsBoolean(): void
    {
        $parameter = $this->createParameter('key', 'true', ParameterType::BOOLEAN);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getBool('key');

        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('booleanValueProvider')]
    public function testGetBoolHandlesVariousFormats(string $value, bool $expected): void
    {
        $parameter = $this->createParameter('key', $value, ParameterType::BOOLEAN);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getBool('key');

        $this->assertSame($expected, $result);
    }

    public static function booleanValueProvider(): iterable
    {
        yield ['true', true];
        yield ['1', true];
        yield ['yes', true];
        yield ['false', false];
        yield ['0', false];
        yield ['no', false];
    }

    public function testGetBoolThrowsExceptionForInvalidValue(): void
    {
        $parameter = $this->createParameter('key', 'invalid', ParameterType::BOOLEAN);
        $this->mockCacheAndRepository('key', $parameter);

        $this->expectException(\InvalidArgumentException::class);

        $this->provider->getBool('key');
    }

    public function testGetJsonReturnsArray(): void
    {
        $parameter = $this->createParameter('key', '{"name": "test", "value": 42}', ParameterType::JSON);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getJson('key');

        $this->assertSame(['name' => 'test', 'value' => 42], $result);
    }

    public function testGetJsonThrowsExceptionForInvalidJson(): void
    {
        $parameter = $this->createParameter('key', '{invalid json}', ParameterType::JSON);
        $this->mockCacheAndRepository('key', $parameter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/contains invalid JSON/');

        $this->provider->getJson('key');
    }

    public function testGetListReturnsArrayOfStrings(): void
    {
        $parameter = $this->createParameter('key', "line1\nline2\nline3", ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key');

        $this->assertSame(['line1', 'line2', 'line3'], $result);
    }

    public function testGetListFiltersEmptyLines(): void
    {
        $parameter = $this->createParameter('key', "line1\n\nline2\n  \nline3", ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key');

        $this->assertSame(['line1', 'line2', 'line3'], \array_values($result));
    }

    public function testGetListWithCommaSeparator(): void
    {
        $parameter = $this->createParameter('key', 'item1,item2,item3', ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key', null, ',');

        $this->assertSame(['item1', 'item2', 'item3'], $result);
    }

    public function testGetListWithSemicolonSeparator(): void
    {
        $parameter = $this->createParameter('key', 'item1;item2;item3', ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key', null, ';');

        $this->assertSame(['item1', 'item2', 'item3'], $result);
    }

    public function testGetListWithCustomSeparatorFiltersEmpty(): void
    {
        $parameter = $this->createParameter('key', 'item1,,item2, ,item3', ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key', null, ',');

        $this->assertSame(['item1', 'item2', 'item3'], \array_values($result));
    }

    public function testGetListWithPipeSeparator(): void
    {
        $parameter = $this->createParameter('key', 'value1|value2|value3', ParameterType::LIST);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->getList('key', null, '|');

        $this->assertSame(['value1', 'value2', 'value3'], $result);
    }

    public function testHasReturnsTrueWhenParameterExists(): void
    {
        $parameter = $this->createParameter('key', 'value', ParameterType::STRING);
        $this->mockCacheAndRepository('key', $parameter);

        $result = $this->provider->has('key');

        $this->assertTrue($result);
    }

    public function testHasReturnsFalseWhenParameterNotFound(): void
    {
        $this->mockCacheAndRepository('key', null);

        $result = $this->provider->has('key');

        $this->assertFalse($result);
    }

    public function testClearCacheClearsAllCache(): void
    {
        // Simulate multiple cached parameters
        $param1 = $this->createParameter('key1', 'value1', ParameterType::STRING);
        $param2 = $this->createParameter('key2', 'value2', ParameterType::STRING);

        $this->repository
            ->method('findAll')
            ->willReturn([$param1, $param2]);

        $this->cache->expects($this->exactly(2))
            ->method('deleteItem')
            ->willReturnCallback(function (string $key) {
                $this->assertStringContainsString('test_param.', $key);

                return true;
            });

        $this->provider->clearCache();
    }

    public function testClearCacheClearsSpecificKey(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with('test_param.specific_key')
            ->willReturn(true);

        $this->provider->clearCache('specific_key');
    }

    private function createParameter(string $key, string $value, ParameterType $type): PlatformParameter
    {
        $parameter = new PlatformParameter();
        $parameter->setKey($key);
        $parameter->setValue($value);
        $parameter->setType($type);
        $parameter->setLabel('Test');

        return $parameter;
    }

    private function mockCacheAndRepository(string $key, ?PlatformParameter $parameter): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('get')->willReturn(null);

        $this->cache->method('getItem')
            ->with("test_param.{$key}")
            ->willReturn($cacheItem);

        $this->repository->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($parameter);
    }
}
