<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Contract;

use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;

interface PlatformParameterProviderInterface
{
    /**
     * Get a string parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     */
    public function getString(string $key, ?string $default = null): string;

    /**
     * Get an integer parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     */
    public function getInt(string $key, ?int $default = null): int;

    /**
     * Get a boolean parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     */
    public function getBool(string $key, ?bool $default = null): bool;

    /**
     * Get a JSON parameter value as array.
     *
     * @param array<mixed>|null $default
     *
     * @return array<mixed>
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     * @throws \JsonException if JSON is invalid
     */
    public function getJson(string $key, ?array $default = null): array;

    /**
     * Get a list parameter value as array.
     *
     * @param string[]|null $default
     * @param string $separator Character(s) to split the value by (default: newline)
     *
     * @return string[]
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     */
    public function getList(string $key, ?array $default = null, string $separator = "\n"): array;

    /**
     * Get a float parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     */
    public function getFloat(string $key, ?float $default = null): float;

    /**
     * Get a datetime parameter value.
     *
     * @param string|null $format Expected date format (default: tries common formats like Y-m-d, Y-m-d H:i:s, ISO8601)
     *
     * @throws ParameterNotFoundException if parameter not found and no default provided
     * @throws \InvalidArgumentException if value cannot be parsed as datetime
     */
    public function getDateTime(string $key, ?\DateTimeImmutable $default = null, ?string $format = null): \DateTimeImmutable;

    /**
     * Check if a parameter exists.
     */
    public function has(string $key): bool;

    /**
     * Clear cached parameters.
     *
     * @param string|null $key Specific parameter key to clear, or null to clear all
     */
    public function clearCache(?string $key = null): void;
}
