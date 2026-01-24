<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Entity;

use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use PHPUnit\Framework\TestCase;

final class AbstractPlatformParameterTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $parameter = $this->createParameter();

        $this->assertNotNull($parameter->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $parameter->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $parameter->getUpdatedAt());
        $this->assertEqualsWithDelta(
            $parameter->getCreatedAt()->getTimestamp(),
            $parameter->getUpdatedAt()->getTimestamp(),
            1,
            'Created and updated timestamps should be nearly equal'
        );
    }

    public function testKeyCanBeSetAndRetrieved(): void
    {
        $parameter = $this->createParameter();
        $parameter->setKey('test_key');

        $this->assertSame('test_key', $parameter->getKey());
    }

    public function testValueCanBeSetAndRetrieved(): void
    {
        $parameter = $this->createParameter();
        $parameter->setValue('test_value');

        $this->assertSame('test_value', $parameter->getValue());
    }

    public function testTypeCanBeSetAndRetrieved(): void
    {
        $parameter = $this->createParameter();
        $parameter->setType(ParameterType::STRING);

        $this->assertSame(ParameterType::STRING, $parameter->getType());
    }

    public function testLabelCanBeSetAndRetrieved(): void
    {
        $parameter = $this->createParameter();
        $parameter->setLabel('Test Label');

        $this->assertSame('Test Label', $parameter->getLabel());
    }

    public function testDescriptionCanBeSetAndRetrieved(): void
    {
        $parameter = $this->createParameter();
        $parameter->setDescription('Test description');

        $this->assertSame('Test description', $parameter->getDescription());
    }

    public function testDescriptionDefaultsToNull(): void
    {
        $parameter = $this->createParameter();

        $this->assertNull($parameter->getDescription());
    }

    public function testUpdateTimestampChangesUpdatedAt(): void
    {
        $parameter = $this->createParameter();
        $originalUpdatedAt = $parameter->getUpdatedAt();

        \usleep(1000); // 1ms delay to ensure different timestamp
        $parameter->updateTimestamp();

        $this->assertGreaterThan($originalUpdatedAt, $parameter->getUpdatedAt());
    }

    public function testCreatedAtDoesNotChange(): void
    {
        $parameter = $this->createParameter();
        $originalCreatedAt = $parameter->getCreatedAt();

        \usleep(1000);
        $parameter->updateTimestamp();

        $this->assertEquals($originalCreatedAt, $parameter->getCreatedAt());
    }

    private function createParameter(): AbstractPlatformParameter
    {
        return new class extends AbstractPlatformParameter {
            private int $id = 1;

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
