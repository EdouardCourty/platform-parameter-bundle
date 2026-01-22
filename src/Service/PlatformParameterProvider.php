<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use function array_filter;
use function array_map;
use function assert;
use function explode;
use function filter_var;
use function is_array;
use function is_numeric;
use function json_decode;
use function sprintf;
use function trim;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const JSON_THROW_ON_ERROR;

final class PlatformParameterProvider implements PlatformParameterProviderInterface
{
    /**
     * @param class-string<AbstractPlatformParameter> $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $entityClass,
        private readonly int $cacheTtl,
        private readonly string $cacheKeyPrefix,
    ) {
    }

    public function getString(string $key, ?string $default = null): string
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        return trim($parameter->getValue());
    }

    public function getInt(string $key, ?int $default = null): int
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        $value = $parameter->getValue();
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" value "%s" is not a valid integer.', $key, $value));
        }

        return (int) $value;
    }

    public function getBool(string $key, ?bool $default = null): bool
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        $value = $parameter->getValue();
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (null === $result) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" value "%s" is not a valid boolean.', $key, $value));
        }

        return $result;
    }

    /**
     * @param array<mixed>|null $default
     *
     * @return array<mixed>
     */
    public function getJson(string $key, ?array $default = null): array
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        try {
            $decoded = json_decode($parameter->getValue(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" contains invalid JSON: %s', $key, $e->getMessage()), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" JSON must decode to an array.', $key));
        }

        return $decoded;
    }

    /**
     * @param string[]|null $default
     *
     * @return string[]
     */
    public function getList(string $key, ?array $default = null, string $separator = "\n"): array
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            return $default ?? [];
        }

        assert('' !== $separator, 'Separator cannot be empty');

        $lines = explode($separator, $parameter->getValue());
        $list = array_map('trim', $lines);

        /* @var string[] */
        return array_filter($list, fn (string $line) => '' !== $line);
    }

    public function getFloat(string $key, ?float $default = null): float
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        $value = $parameter->getValue();
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" value "%s" is not a valid float.', $key, $value));
        }

        return (float) $value;
    }

    public function getDateTime(string $key, ?DateTimeImmutable $default = null, ?string $format = null): DateTimeImmutable
    {
        $parameter = $this->fetchParameter($key, $default);
        if (null === $parameter) {
            assert(null !== $default);

            return $default;
        }

        $value = trim($parameter->getValue());

        // Try with specific format if provided
        if (null !== $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if (false === $date) {
                throw new InvalidArgumentException(sprintf('Parameter "%s" value "%s" cannot be parsed with format "%s".', $key, $value, $format));
            }

            return $date;
        }

        // Try common formats
        $formats = [
            DateTimeInterface::ATOM,           // ISO 8601: 2005-08-15T15:52:01+00:00
            'Y-m-d H:i:s',                      // MySQL datetime: 2005-08-15 15:52:01
            'Y-m-d',                            // Date only: 2005-08-15
            'd/m/Y',                            // French: 15/08/2005
            'd/m/Y H:i:s',                      // French with time: 15/08/2005 15:52:01
            'U',                                // Unix timestamp: 1123456789
        ];

        foreach ($formats as $tryFormat) {
            $date = DateTimeImmutable::createFromFormat($tryFormat, $value);
            if (false !== $date) {
                return $date;
            }
        }

        // Last resort: try native parser
        try {
            return new DateTimeImmutable($value);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" value "%s" cannot be parsed as datetime.', $key, $value), 0, $e);
        }
    }

    public function has(string $key): bool
    {
        try {
            $this->fetchParameter($key, null);

            return true;
        } catch (ParameterNotFoundException) {
            return false;
        }
    }

    public function clearCache(?string $key = null): void
    {
        if (null !== $key) {
            // Clear specific parameter cache
            $this->cache->deleteItem($this->getCacheKey($key));

            return;
        }

        // Clear all parameter caches
        if ($this->cache instanceof TagAwareCacheInterface) {
            // If using TagAwareAdapter, invalidate by tag
            $this->cache->invalidateTags([$this->cacheKeyPrefix]);
        } else {
            // Fallback: fetch all parameter keys from database and delete their cache
            $repository = $this->entityManager->getRepository($this->entityClass);
            $parameters = $repository->findAll();

            foreach ($parameters as $parameter) {
                $this->cache->deleteItem($this->getCacheKey($parameter->getKey()));
            }
        }
    }

    private function fetchParameter(string $key, mixed $default): ?AbstractPlatformParameter
    {
        $cacheKey = $this->getCacheKey($key);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();
            \assert($value instanceof AbstractPlatformParameter);

            return $value;
        }

        $repository = $this->entityManager->getRepository($this->entityClass);
        /** @var AbstractPlatformParameter|null $parameter */
        $parameter = $repository->findOneBy(['key' => $key]);

        if (null === $parameter && null === $default) {
            throw ParameterNotFoundException::forKey($key);
        }

        if (null !== $parameter) {
            $cacheItem->set($parameter);
            $cacheItem->expiresAfter($this->cacheTtl);

            // Tag the cache item for easier invalidation
            if ($cacheItem instanceof ItemInterface) {
                $cacheItem->tag($this->cacheKeyPrefix);
            }

            $this->cache->save($cacheItem);
        }

        return $parameter;
    }

    private function getCacheKey(string $key): string
    {
        return sprintf('%s.%s', $this->cacheKeyPrefix, $key);
    }
}
