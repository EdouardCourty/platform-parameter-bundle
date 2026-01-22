<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Enum;

use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use PHPUnit\Framework\TestCase;

class ParameterTypeTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ParameterType::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(ParameterType::STRING, $cases);
        $this->assertContains(ParameterType::INTEGER, $cases);
        $this->assertContains(ParameterType::BOOLEAN, $cases);
        $this->assertContains(ParameterType::JSON, $cases);
        $this->assertContains(ParameterType::LIST, $cases);
        $this->assertContains(ParameterType::FLOAT, $cases);
        $this->assertContains(ParameterType::DATETIME, $cases);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('labelProvider')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('valueProvider')]
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
