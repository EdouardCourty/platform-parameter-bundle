<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlatformParameterCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(false);
        $this->client->enableProfiler();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Setup database schema
        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testIndexPageLoads(): void
    {
        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testIndexPageDisplaysParameters(): void
    {
        $parameter = $this->createTestParameter('test_key', 'test_value');

        $this->client->request('GET', $this->getIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'test_key');
    }

    public function testNewPageLoads(): void
    {
        $this->client->request('GET', $this->getNewUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCanCreateNewParameter(): void
    {
        $crawler = $this->client->request('GET', $this->getNewUrl());

        $form = $crawler->selectButton('Create')->form([
            'PlatformParameter[key]' => 'new_param',
            'PlatformParameter[label]' => 'New Parameter',
            'PlatformParameter[type]' => 'string',
            'PlatformParameter[value]' => 'test value',
            'PlatformParameter[description]' => 'Test description',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        $parameter = $this->entityManager->getRepository(PlatformParameter::class)
            ->findOneBy(['key' => 'new_param']);

        $this->assertNotNull($parameter);
        $this->assertSame('New Parameter', $parameter->getLabel());
        $this->assertSame('test value', $parameter->getValue());
        $this->assertSame(ParameterType::STRING, $parameter->getType());
    }

    public function testEditPageLoads(): void
    {
        $parameter = $this->createTestParameter('edit_test', 'original_value');

        $this->client->request('GET', $this->getEditUrl($parameter->getId()->toString()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[value="edit_test"][disabled]');
    }

    public function testCanEditParameter(): void
    {
        $parameter = $this->createTestParameter('edit_key', 'original_value');
        $parameterId = $parameter->getId();

        $crawler = $this->client->request('GET', $this->getEditUrl($parameterId->toString()));

        $form = $crawler->selectButton('Save changes')->form([
            'PlatformParameter[value]' => 'updated value',
            'PlatformParameter[description]' => 'Updated description',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        $this->entityManager->clear();
        $updatedParameter = $this->entityManager->getRepository(PlatformParameter::class)->find($parameterId);

        $this->assertSame('updated value', $updatedParameter->getValue());
        $this->assertSame('Updated description', $updatedParameter->getDescription());
    }

    public function testDetailPageLoads(): void
    {
        $parameter = $this->createTestParameter('detail_test', 'detail_value');

        $this->client->request('GET', $this->getDetailUrl($parameter->getId()->toString()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'detail_test');
        $this->assertSelectorTextContains('body', 'detail_value');
    }

    public function testCanDeleteParameter(): void
    {
        $parameter = $this->createTestParameter('delete_test', 'delete_value');
        $id = $parameter->getId();

        // Go to detail page where delete button/form exists
        $crawler = $this->client->request('GET', $this->getDetailUrl($id->toString()));

        // EasyAdmin has a delete action button in the detail page
        // Find the delete form and submit it
        $deleteForm = $crawler->filter('form[action*="delete"]')->first();
        
        if ($deleteForm->count() > 0) {
            // Extract and submit the form
            $form = $deleteForm->form();
            $this->client->submit($form);
        } else {
            // If no form found, the delete might be via a different mechanism
            // In that case, manually create the request with the token from the crawler
            $token = $crawler->filter('input[name="token"]')->attr('value');
            $this->client->request('POST', $this->getDeleteUrl($id->toString()), [
                'token' => $token,
            ]);
        }

        $this->assertResponseRedirects();

        $deletedParameter = $this->entityManager->getRepository(PlatformParameter::class)
            ->find($id);

        $this->assertNull($deletedParameter);
    }

    public function testListPageShowsTypeBadges(): void
    {
        $this->createTestParameter('str_param', 'value', ParameterType::STRING);
        $this->createTestParameter('int_param', '42', ParameterType::INTEGER);

        $this->client->request('GET', $this->getIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'String');
        $this->assertSelectorTextContains('body', 'Integer');
    }

    private function createTestParameter(string $key, string $value, ParameterType $type = ParameterType::STRING): PlatformParameter
    {
        $parameter = new PlatformParameter();
        $parameter->setKey($key);
        $parameter->setValue($value);
        $parameter->setType($type);
        $parameter->setLabel('Test '.$key);
        $parameter->setDescription('Test description');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        return $parameter;
    }

    private function setupDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function getCsrfToken(string $tokenId): string
    {
        // Ensure session is started
        $session = $this->client->getContainer()->get('session.factory')->createSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        return static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken($tokenId)
            ->getValue();
    }

    private function getIndexUrl(): string
    {
        return '/admin/platform-parameter';
    }

    private function getNewUrl(): string
    {
        return '/admin/platform-parameter/new';
    }

    private function getEditUrl(string $entityId): string
    {
        return '/admin/platform-parameter/' . $entityId . '/edit';
    }

    private function getDetailUrl(string $entityId): string
    {
        return '/admin/platform-parameter/' . $entityId;
    }

    private function getDeleteUrl(string $entityId): string
    {
        return '/admin/platform-parameter/' . $entityId . '/delete';
    }
}
