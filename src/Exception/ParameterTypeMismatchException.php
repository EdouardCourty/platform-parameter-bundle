<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Exception;

use Ecourty\PlatformParameterBundle\Enum\ParameterType;

class ParameterTypeMismatchException extends \InvalidArgumentException
{
    public static function create(string $key, ParameterType $expectedType, ParameterType $actualType): self
    {
        return new self(\sprintf(
            'Cannot set parameter "%s" with type %s: parameter type is %s.',
            $key,
            $expectedType->value,
            $actualType->value
        ));
    }
}
