<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Exception\ParameterTypeMismatchException;
use Ecourty\PlatformParameterBundle\Service\PlatformParameterWriter;
use PHPUnit\Framework\TestCase;

class PlatformParameterWriterTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;
    private PlatformParameterWriter $writer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(PlatformParameter::class)
            ->willReturn($this->repository);

        $this->writer = new PlatformParameterWriter(
            $this->entityManager,
            PlatformParameter::class,
        );
    }

    public function testSetStringUpdatesValue(): void
    {
        $parameter = $this->createParameter('site_name', 'Old Name', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setString('site_name', 'New Name');

        $this->assertSame('New Name', $parameter->getValue());
    }

    public function testSetStringTrimsValue(): void
    {
        $parameter = $this->createParameter('site_name', 'Old Name', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setString('site_name', '  Trimmed Value  ');

        $this->assertSame('Trimmed Value', $parameter->getValue());
    }

    public function testSetStringThrowsExceptionWhenParameterNotFound(): void
    {
        $this->mockRepository('missing_key', null);

        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "missing_key" not found.');

        $this->writer->setString('missing_key', 'value');
    }

    public function testSetStringThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('max_uploads', '10', ParameterType::INTEGER);
        $this->mockRepository('max_uploads', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Cannot set parameter "max_uploads" with type string: parameter type is integer.');

        $this->writer->setString('max_uploads', 'value');
    }

    public function testSetIntUpdatesValue(): void
    {
        $parameter = $this->createParameter('max_uploads', '10', ParameterType::INTEGER);
        $this->mockRepository('max_uploads', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setInt('max_uploads', 100);

        $this->assertSame('100', $parameter->getValue());
    }

    public function testSetIntThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Cannot set parameter "site_name" with type integer: parameter type is string.');

        $this->writer->setInt('site_name', 42);
    }

    public function testSetBoolUpdatesValueWithTrue(): void
    {
        $parameter = $this->createParameter('maintenance_mode', '0', ParameterType::BOOLEAN);
        $this->mockRepository('maintenance_mode', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setBool('maintenance_mode', true);

        $this->assertSame('1', $parameter->getValue());
    }

    public function testSetBoolUpdatesValueWithFalse(): void
    {
        $parameter = $this->createParameter('maintenance_mode', '1', ParameterType::BOOLEAN);
        $this->mockRepository('maintenance_mode', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setBool('maintenance_mode', false);

        $this->assertSame('0', $parameter->getValue());
    }

    public function testSetBoolThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);

        $this->writer->setBool('site_name', true);
    }

    public function testSetJsonUpdatesValue(): void
    {
        $parameter = $this->createParameter('config', '{"old":"value"}', ParameterType::JSON);
        $this->mockRepository('config', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setJson('config', ['key' => 'value', 'nested' => ['a' => 1]]);

        $this->assertSame('{"key":"value","nested":{"a":1}}', $parameter->getValue());
    }

    public function testSetJsonThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);

        $this->writer->setJson('site_name', ['key' => 'value']);
    }

    public function testSetListUpdatesValueWithDefaultSeparator(): void
    {
        $parameter = $this->createParameter('emails', "old@example.com\nold2@example.com", ParameterType::LIST);
        $this->mockRepository('emails', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setList('emails', ['new@example.com', 'new2@example.com']);

        $this->assertSame("new@example.com\nnew2@example.com", $parameter->getValue());
    }

    public function testSetListUpdatesValueWithCustomSeparator(): void
    {
        $parameter = $this->createParameter('emails', 'old@example.com,old2@example.com', ParameterType::LIST);
        $this->mockRepository('emails', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setList('emails', ['new@example.com', 'new2@example.com'], ',');

        $this->assertSame('new@example.com,new2@example.com', $parameter->getValue());
    }

    public function testSetListThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);

        $this->writer->setList('site_name', ['a', 'b']);
    }

    public function testSetFloatUpdatesValue(): void
    {
        $parameter = $this->createParameter('rate', '3.14', ParameterType::FLOAT);
        $this->mockRepository('rate', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->setFloat('rate', 2.718);

        $this->assertSame('2.718', $parameter->getValue());
    }

    public function testSetFloatThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);

        $this->writer->setFloat('site_name', 3.14);
    }

    public function testSetDateTimeUpdatesValueWithDefaultFormat(): void
    {
        $parameter = $this->createParameter('last_sync', '2024-01-01T00:00:00+00:00', ParameterType::DATETIME);
        $this->mockRepository('last_sync', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $date = new \DateTimeImmutable('2026-01-24 22:00:00');
        $this->writer->setDateTime('last_sync', $date);

        // ATOM format: Y-m-d\TH:i:sP
        $this->assertStringStartsWith('2026-01-24T22:00:00', $parameter->getValue());
    }

    public function testSetDateTimeUpdatesValueWithCustomFormat(): void
    {
        $parameter = $this->createParameter('last_sync', '2024-01-01', ParameterType::DATETIME);
        $this->mockRepository('last_sync', $parameter);

        $this->entityManager->expects($this->once())->method('flush');

        $date = new \DateTimeImmutable('2026-01-24 22:00:00');
        $this->writer->setDateTime('last_sync', $date, 'Y-m-d');

        $this->assertSame('2026-01-24', $parameter->getValue());
    }

    public function testSetDateTimeThrowsExceptionWhenTypeMismatch(): void
    {
        $parameter = $this->createParameter('site_name', 'Site', ParameterType::STRING);
        $this->mockRepository('site_name', $parameter);

        $this->expectException(ParameterTypeMismatchException::class);

        $this->writer->setDateTime('site_name', new \DateTimeImmutable());
    }

    public function testDeleteRemovesParameter(): void
    {
        $parameter = $this->createParameter('to_delete', 'value', ParameterType::STRING);
        $this->mockRepository('to_delete', $parameter);

        $this->entityManager->expects($this->once())->method('remove')->with($parameter);
        $this->entityManager->expects($this->once())->method('flush');

        $this->writer->delete('to_delete');
    }

    public function testDeleteThrowsExceptionWhenParameterNotFound(): void
    {
        $this->mockRepository('missing_key', null);

        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Platform parameter with key "missing_key" not found.');

        $this->writer->delete('missing_key');
    }

    private function createParameter(string $key, string $value, ParameterType $type): PlatformParameter
    {
        $parameter = new PlatformParameter();
        $parameter->setKey($key);
        $parameter->setValue($value);
        $parameter->setType($type);
        $parameter->setLabel('Test Label');

        return $parameter;
    }

    private function mockRepository(string $key, ?PlatformParameter $parameter): void
    {
        $this->repository
            ->method('findOneBy')
            ->with(['key' => $key])
            ->willReturn($parameter);
    }
}
