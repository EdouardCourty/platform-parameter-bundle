<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Enum;

use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParameterTypeTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function testGetLabel(ParameterType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->getLabel());
    }

    public static function labelProvider(): iterable
    {
        yield [ParameterType::STRING, 'String'];
        yield [ParameterType::INTEGER, 'Integer'];
        yield [ParameterType::BOOLEAN, 'Boolean'];
        yield [ParameterType::JSON, 'JSON'];
        yield [ParameterType::LIST, 'List'];
        yield [ParameterType::FLOAT, 'Float'];
        yield [ParameterType::DATETIME, 'DateTime'];
    }

    #[DataProvider('valueProvider')]
    public function testFromValue(string $value, ParameterType $expectedType): void
    {
        $this->assertSame($expectedType, ParameterType::from($value));
    }

    public static function valueProvider(): iterable
    {
        yield ['string', ParameterType::STRING];
        yield ['integer', ParameterType::INTEGER];
        yield ['boolean', ParameterType::BOOLEAN];
        yield ['json', ParameterType::JSON];
        yield ['list', ParameterType::LIST];
        yield ['float', ParameterType::FLOAT];
        yield ['datetime', ParameterType::DATETIME];
    }
}
