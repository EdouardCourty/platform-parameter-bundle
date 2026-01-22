<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Enum;

enum ParameterType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case LIST = 'list';
    case FLOAT = 'float';
    case DATETIME = 'datetime';

    public function getLabel(): string
    {
        return match ($this) {
            self::STRING => 'String',
            self::INTEGER => 'Integer',
            self::BOOLEAN => 'Boolean',
            self::JSON => 'JSON',
            self::LIST => 'List',
            self::FLOAT => 'Float',
            self::DATETIME => 'DateTime',
        };
    }
}
