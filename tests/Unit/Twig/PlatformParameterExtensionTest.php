<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Twig;

use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Twig\PlatformParameterExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class PlatformParameterExtensionTest extends TestCase
{
    private PlatformParameterProviderInterface $provider;
    private PlatformParameterExtension $extension;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(PlatformParameterProviderInterface::class);
        $this->extension = new PlatformParameterExtension($this->provider);
    }

    public function testGetFunctionsReturnsAllExpectedFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(7, $functions);

        $names = \array_map(static fn (TwigFunction $f) => $f->getName(), $functions);

        $this->assertContains('platform_parameter_string', $names);
        $this->assertContains('platform_parameter_int', $names);
        $this->assertContains('platform_parameter_bool', $names);
        $this->assertContains('platform_parameter_float', $names);
        $this->assertContains('platform_parameter_json', $names);
        $this->assertContains('platform_parameter_list', $names);
        $this->assertContains('platform_parameter_datetime', $names);
    }

    // --- getString ---

    public function testGetStringReturnsValue(): void
    {
        $this->provider->method('getString')->with('key', null)->willReturn('hello');

        $this->assertSame('hello', $this->extension->getString('key'));
    }

    public function testGetStringReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getString')->with('key', 'fallback')->willReturn('fallback');

        $this->assertSame('fallback', $this->extension->getString('key', 'fallback'));
    }

    public function testGetStringReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getString')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getString('key'));
    }

    // --- getInt ---

    public function testGetIntReturnsValue(): void
    {
        $this->provider->method('getInt')->with('key', null)->willReturn(42);

        $this->assertSame(42, $this->extension->getInt('key'));
    }

    public function testGetIntReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getInt')->with('key', 10)->willReturn(10);

        $this->assertSame(10, $this->extension->getInt('key', 10));
    }

    public function testGetIntReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getInt')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getInt('key'));
    }

    // --- getBool ---

    public function testGetBoolReturnsValue(): void
    {
        $this->provider->method('getBool')->with('key', null)->willReturn(true);

        $this->assertTrue($this->extension->getBool('key'));
    }

    public function testGetBoolReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getBool')->with('key', false)->willReturn(false);

        $this->assertFalse($this->extension->getBool('key', false));
    }

    public function testGetBoolReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getBool')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getBool('key'));
    }

    // --- getFloat ---

    public function testGetFloatReturnsValue(): void
    {
        $this->provider->method('getFloat')->with('key', null)->willReturn(3.14);

        $this->assertSame(3.14, $this->extension->getFloat('key'));
    }

    public function testGetFloatReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getFloat')->with('key', 1.0)->willReturn(1.0);

        $this->assertSame(1.0, $this->extension->getFloat('key', 1.0));
    }

    public function testGetFloatReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getFloat')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getFloat('key'));
    }

    // --- getJson ---

    public function testGetJsonReturnsValue(): void
    {
        $this->provider->method('getJson')->with('key', null)->willReturn(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->extension->getJson('key'));
    }

    public function testGetJsonReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getJson')->with('key', [])->willReturn([]);

        $this->assertSame([], $this->extension->getJson('key', []));
    }

    public function testGetJsonReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getJson')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getJson('key'));
    }

    // --- getList ---

    public function testGetListReturnsValue(): void
    {
        $this->provider->method('getList')->with('key', null, "\n")->willReturn(['a', 'b']);

        $this->assertSame(['a', 'b'], $this->extension->getList('key'));
    }

    public function testGetListReturnsDefaultWhenNotFound(): void
    {
        $this->provider->method('getList')->with('key', [], "\n")->willReturn([]);

        $this->assertSame([], $this->extension->getList('key', []));
    }

    public function testGetListReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getList')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getList('key'));
    }

    public function testGetListUsesCustomSeparator(): void
    {
        $this->provider->method('getList')->with('key', null, ',')->willReturn(['x', 'y']);

        $this->assertSame(['x', 'y'], $this->extension->getList('key', null, ','));
    }

    // --- getDateTime ---

    public function testGetDateTimeReturnsValue(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $this->provider->method('getDateTime')->with('key', null, null)->willReturn($date);

        $this->assertSame($date, $this->extension->getDateTime('key'));
    }

    public function testGetDateTimeReturnsDefaultWhenNotFound(): void
    {
        $default = new \DateTimeImmutable('2000-01-01');
        $this->provider->method('getDateTime')->with('key', $default, null)->willReturn($default);

        $this->assertSame($default, $this->extension->getDateTime('key', $default));
    }

    public function testGetDateTimeReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $this->provider->method('getDateTime')->willThrowException(new ParameterNotFoundException('key'));

        $this->assertNull($this->extension->getDateTime('key'));
    }

    public function testGetDateTimeUsesCustomFormat(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');
        $this->provider->method('getDateTime')->with('key', null, 'Y-m-d')->willReturn($date);

        $this->assertSame($date, $this->extension->getDateTime('key', null, 'Y-m-d'));
    }
}
