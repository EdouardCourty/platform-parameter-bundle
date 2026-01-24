<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Contract;

use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Exception\ParameterTypeMismatchException;

/**
 * Service for writing/updating platform parameter values.
 *
 * This interface provides type-safe methods for updating parameter values.
 * All methods require the parameter to exist and match the expected type.
 *
 * Cache clearing and event dispatching are handled automatically via Doctrine listeners.
 */
interface PlatformParameterWriterInterface
{
    /**
     * Update a string parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not STRING
     */
    public function setString(string $key, string $value): void;

    /**
     * Update an integer parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not INTEGER
     */
    public function setInt(string $key, int $value): void;

    /**
     * Update a boolean parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not BOOLEAN
     */
    public function setBool(string $key, bool $value): void;

    /**
     * Update a JSON parameter value.
     *
     * @param array<mixed> $value
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not JSON
     * @throws \JsonException if value cannot be encoded to JSON
     */
    public function setJson(string $key, array $value): void;

    /**
     * Update a list parameter value.
     *
     * @param string[] $value
     * @param string $separator Character(s) to join array elements (default: newline)
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not LIST
     */
    public function setList(string $key, array $value, string $separator = "\n"): void;

    /**
     * Update a float parameter value.
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not FLOAT
     */
    public function setFloat(string $key, float $value): void;

    /**
     * Update a datetime parameter value.
     *
     * @param string|null $format Format to use for string conversion (default: ISO8601/ATOM)
     *
     * @throws ParameterNotFoundException if parameter not found
     * @throws ParameterTypeMismatchException if parameter type is not DATETIME
     */
    public function setDateTime(string $key, \DateTimeImmutable $value, ?string $format = null): void;

    /**
     * Delete a parameter by key.
     *
     * @throws ParameterNotFoundException if parameter not found
     */
    public function delete(string $key): void;
}
