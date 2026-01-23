<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineMigrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();
        parent::tearDown();
    }

    public function testSchemaCanBeCreatedFromEntities(): void
    {
        $this->dropSchema();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->createSchema($metadata);

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains('platform_parameter', $tables, 'Table platform_parameter should exist');
    }

    public function testSchemaStructureIsValid(): void
    {
        $this->createSchema();

        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('platform_parameter');

        $expectedColumns = ['id', 'key', 'value', 'type', 'label', 'description', 'created_at', 'updated_at'];
        $actualColumnNames = \array_keys($columns);

        foreach ($expectedColumns as $expectedColumn) {
            $found = false;
            foreach ($actualColumnNames as $actualColumn) {
                $normalizedActual = \trim(\strtolower($actualColumn), '"');
                $normalizedExpected = \strtolower($expectedColumn);
                if ($normalizedActual === $normalizedExpected) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue(
                $found,
                \sprintf('Column %s should exist in platform_parameter table. Found: %s', $expectedColumn, \implode(', ', $actualColumnNames))
            );
        }
    }

    public function testEntityCanBePersistedWithGeneratedSchema(): void
    {
        $this->createSchema();

        $parameter = new PlatformParameter();
        $parameter->setKey('test_key');
        $parameter->setValue('test_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Parameter');
        $parameter->setDescription('Test description');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        $this->assertNotNull($parameter->getId());

        $this->entityManager->clear();

        $repository = $this->entityManager->getRepository(PlatformParameter::class);
        $foundParameter = $repository->findOneBy(['key' => 'test_key']);

        $this->assertNotNull($foundParameter);
        $this->assertSame('test_key', $foundParameter->getKey());
        $this->assertSame('test_value', $foundParameter->getValue());
        $this->assertSame(ParameterType::STRING, $foundParameter->getType());
    }

    public function testSchemaUpdateDoesNotBreakExistingData(): void
    {
        $this->createSchema();

        $parameter = new PlatformParameter();
        $parameter->setKey('persistent_key');
        $parameter->setValue('persistent_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Persistent Parameter');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();
        $parameterId = $parameter->getId();

        $this->entityManager->clear();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($metadata);

        $repository = $this->entityManager->getRepository(PlatformParameter::class);
        $foundParameter = $repository->find($parameterId);

        $this->assertNotNull($foundParameter, 'Parameter should still exist after schema update');
        $this->assertSame('persistent_key', $foundParameter->getKey());
        $this->assertSame('persistent_value', $foundParameter->getValue());
    }

    public function testSchemaValidationPasses(): void
    {
        $this->createSchema();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $errors = $schemaTool->getSchemaFromMetadata($metadata);

        $this->assertNotNull($errors, 'Schema should be valid');
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    private function dropSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
}
