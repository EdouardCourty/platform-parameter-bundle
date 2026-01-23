<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Entity;

use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class PlatformParameterTest extends TestCase
{
    public function testCanCreateParameter(): void
    {
        $parameter = new PlatformParameter();

        $this->assertInstanceOf(Uuid::class, $parameter->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $parameter->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $parameter->getUpdatedAt());
    }

    public function testCanSetAndGetKey(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('test_key');

        $this->assertSame('test_key', $parameter->getKey());
    }

    public function testCanSetAndGetValue(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setValue('test_value');

        $this->assertSame('test_value', $parameter->getValue());
    }

    public function testCanSetAndGetType(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setType(ParameterType::STRING);

        $this->assertSame(ParameterType::STRING, $parameter->getType());
    }

    public function testCanSetAndGetLabel(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setLabel('Test Label');

        $this->assertSame('Test Label', $parameter->getLabel());
    }

    public function testCanSetAndGetDescription(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setDescription('Test description');

        $this->assertSame('Test description', $parameter->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $parameter = new PlatformParameter();

        $this->assertNull($parameter->getDescription());
    }

    public function testUpdateTimestampIsCalledOnPreupdate(): void
    {
        $parameter = new PlatformParameter();
        $originalUpdatedAt = $parameter->getUpdatedAt();

        \usleep(1000);
        $parameter->updateTimestamp();

        $this->assertGreaterThan($originalUpdatedAt, $parameter->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $parameter = (new PlatformParameter())
            ->setKey('test')
            ->setValue('value')
            ->setType(ParameterType::STRING)
            ->setLabel('Label')
            ->setDescription('Description');

        $this->assertSame('test', $parameter->getKey());
        $this->assertSame('value', $parameter->getValue());
        $this->assertSame(ParameterType::STRING, $parameter->getType());
        $this->assertSame('Label', $parameter->getLabel());
        $this->assertSame('Description', $parameter->getDescription());
    }
}
