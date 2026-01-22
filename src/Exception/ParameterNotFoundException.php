<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Exception;

final class ParameterNotFoundException extends \RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(\sprintf('Platform parameter with key "%s" not found.', $key));
    }
}
