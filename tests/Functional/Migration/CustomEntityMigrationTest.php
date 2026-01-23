<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Tests\Fixtures\CustomPlatformParameter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests migration scenarios with custom entities extending AbstractPlatformParameter.
 *
 * This ensures that users can safely extend the base entity with their own fields
 * without breaking the migration system.
 */
class CustomEntityMigrationTest extends KernelTestCase
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
        $this->dropAllTables();
        parent::tearDown();
    }

    public function testCustomEntitySchemaCanBeCreated(): void
    {
        $this->dropAllTables();

        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains('custom_platform_parameter', $tables, 'Custom entity table should exist');
    }

    public function testCustomEntityHasBaseAndCustomColumns(): void
    {
        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('custom_platform_parameter');
        $columnNames = \array_map(fn ($col) => \trim(\strtolower($col), '"'), \array_keys($columns));

        // Base columns from AbstractPlatformParameter
        $baseColumns = ['id', 'key', 'value', 'type', 'label', 'description', 'created_at', 'updated_at'];
        foreach ($baseColumns as $baseColumn) {
            $this->assertContains(
                $baseColumn,
                $columnNames,
                \sprintf('Base column %s should exist in custom entity table', $baseColumn)
            );
        }

        // Custom columns added by CustomPlatformParameter
        $customColumns = ['category', 'sort_order', 'icon'];
        foreach ($customColumns as $customColumn) {
            $this->assertContains(
                $customColumn,
                $columnNames,
                \sprintf('Custom column %s should exist in extended entity table', $customColumn)
            );
        }
    }

    public function testCustomEntityCanBePersisted(): void
    {
        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $customParameter = new CustomPlatformParameter();
        $customParameter->setKey('custom_key');
        $customParameter->setValue('custom_value');
        $customParameter->setType(ParameterType::STRING);
        $customParameter->setLabel('Custom Parameter');
        $customParameter->setCategory('testing');
        $customParameter->setSortOrder(42);
        $customParameter->setIcon('star');

        $this->entityManager->persist($customParameter);
        $this->entityManager->flush();

        $this->assertNotNull($customParameter->getId());

        $this->entityManager->clear();

        $repository = $this->entityManager->getRepository(CustomPlatformParameter::class);
        $found = $repository->findOneBy(['key' => 'custom_key']);

        $this->assertNotNull($found);
        $this->assertInstanceOf(CustomPlatformParameter::class, $found);
        $this->assertSame('custom_key', $found->getKey());
        $this->assertSame('custom_value', $found->getValue());
        $this->assertSame('testing', $found->getCategory());
        $this->assertSame(42, $found->getSortOrder());
        $this->assertSame('star', $found->getIcon());
    }

    public function testBothStandardAndCustomEntitiesCanCoexist(): void
    {
        $this->createSchemaForEntity(PlatformParameter::class);
        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains('platform_parameter', $tables, 'Standard entity table should exist');
        $this->assertContains('custom_platform_parameter', $tables, 'Custom entity table should exist');

        // Test that both can persist data independently
        $standardParam = new PlatformParameter();
        $standardParam->setKey('standard_key');
        $standardParam->setValue('standard_value');
        $standardParam->setType(ParameterType::STRING);
        $standardParam->setLabel('Standard Parameter');

        $customParam = new CustomPlatformParameter();
        $customParam->setKey('custom_key');
        $customParam->setValue('custom_value');
        $customParam->setType(ParameterType::STRING);
        $customParam->setLabel('Custom Parameter');
        $customParam->setCategory('test');

        $this->entityManager->persist($standardParam);
        $this->entityManager->persist($customParam);
        $this->entityManager->flush();

        $this->assertNotNull($standardParam->getId());
        $this->assertNotNull($customParam->getId());
    }

    public function testMigrationFromStandardToCustomEntityStructure(): void
    {
        // Simulate migration scenario: user starts with standard entity, then creates custom one

        // Step 1: Create standard entity schema and populate data
        $this->createSchemaForEntity(PlatformParameter::class);

        $standardParam = new PlatformParameter();
        $standardParam->setKey('existing_param');
        $standardParam->setValue('existing_value');
        $standardParam->setType(ParameterType::STRING);
        $standardParam->setLabel('Existing Parameter');

        $this->entityManager->persist($standardParam);
        $this->entityManager->flush();
        $paramId = $standardParam->getId();

        $this->entityManager->clear();

        // Step 2: Now create custom entity schema (simulating user extending the entity)
        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        // Both tables should exist
        $this->assertContains('platform_parameter', $tables);
        $this->assertContains('custom_platform_parameter', $tables);

        // Original data should still be accessible
        $repository = $this->entityManager->getRepository(PlatformParameter::class);
        $foundParam = $repository->find($paramId);

        $this->assertNotNull($foundParam, 'Original data should still exist');
        $this->assertSame('existing_param', $foundParam->getKey());
    }

    public function testCustomEntitySchemaUpdatePreservesData(): void
    {
        $this->createSchemaForEntity(CustomPlatformParameter::class);

        $customParameter = new CustomPlatformParameter();
        $customParameter->setKey('preserve_test');
        $customParameter->setValue('preserve_value');
        $customParameter->setType(ParameterType::STRING);
        $customParameter->setLabel('Preserve Test');
        $customParameter->setCategory('original_category');

        $this->entityManager->persist($customParameter);
        $this->entityManager->flush();
        $parameterId = $customParameter->getId();

        $this->entityManager->clear();

        // Simulate schema update (e.g., user adds another field to their custom entity)
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [$this->entityManager->getClassMetadata(CustomPlatformParameter::class)];
        $schemaTool->updateSchema($metadata);

        // Data should be preserved
        $repository = $this->entityManager->getRepository(CustomPlatformParameter::class);
        $foundParameter = $repository->find($parameterId);

        $this->assertNotNull($foundParameter);
        $this->assertSame('preserve_test', $foundParameter->getKey());
        $this->assertSame('preserve_value', $foundParameter->getValue());
        $this->assertSame('original_category', $foundParameter->getCategory());
    }

    private function createSchemaForEntity(string $entityClass): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [$this->entityManager->getClassMetadata($entityClass)];
        $schemaTool->createSchema($metadata);
    }

    private function dropAllTables(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
}
