<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Functional\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Twig\PlatformParameterExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class PlatformParameterExtensionIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Environment $twig;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->twig = static::getContainer()->get(Environment::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->seedParameters();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testExtensionIsRegistered(): void
    {
        $extensions = $this->twig->getExtensions();
        $extensionClasses = \array_keys($extensions);

        $this->assertContains(
            PlatformParameterExtension::class,
            $extensionClasses,
        );
    }

    public function testStringFunctionRendersValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="string">My Site</p>', $html);
    }

    public function testStringFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="string_default">default_string</p>', $html);
    }

    public function testStringFunctionRendersEmptyWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="string_missing"></p>', $html);
    }

    public function testIntFunctionRendersValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="int">20</p>', $html);
    }

    public function testIntFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="int_default">99</p>', $html);
    }

    public function testIntFunctionRendersEmptyWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="int_missing"></p>', $html);
    }

    public function testBoolFunctionRendersTrueValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="bool_true">true</p>', $html);
    }

    public function testBoolFunctionRendersFalseValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="bool_false">false</p>', $html);
    }

    public function testBoolFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="bool_default">false</p>', $html);
    }

    public function testBoolFunctionRendersNullWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="bool_missing">null</p>', $html);
    }

    public function testFloatFunctionRendersValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="float">3.14</p>', $html);
    }

    public function testFloatFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="float_default">1.5</p>', $html);
    }

    public function testFloatFunctionRendersEmptyWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="float_missing"></p>', $html);
    }

    public function testListFunctionRendersItems(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<li>alice@example.com</li>', $html);
        $this->assertStringContainsString('<li>bob@example.com</li>', $html);
    }

    public function testListFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="list_default">a,b</p>', $html);
    }

    public function testListFunctionRendersNullWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="list_missing">null</p>', $html);
    }

    public function testJsonFunctionRendersValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="json">bar</p>', $html);
    }

    public function testJsonFunctionRendersDefaultWhenMissing(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="json_default">y</p>', $html);
    }

    public function testJsonFunctionRendersNullWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="json_missing">null</p>', $html);
    }

    public function testDatetimeFunctionRendersValue(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="datetime">2024-06-15</p>', $html);
    }

    public function testDatetimeFunctionRendersNullWhenMissingAndNoDefault(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<p id="datetime_missing">null</p>', $html);
    }

    private function render(): string
    {
        return $this->twig->render('platform_parameter_extension_test.html.twig');
    }

    private function seedParameters(): void
    {
        $parameters = [
            ['site_name', 'My Site', ParameterType::STRING],
            ['max_uploads', '20', ParameterType::INTEGER],
            ['feature_enabled', '1', ParameterType::BOOLEAN],
            ['feature_disabled', '0', ParameterType::BOOLEAN],
            ['rate', '3.14', ParameterType::FLOAT],
            ['emails', "alice@example.com\nbob@example.com", ParameterType::LIST],
            ['config', '{"key":"bar"}', ParameterType::JSON],
            ['last_sync', '2024-06-15', ParameterType::DATETIME],
        ];

        foreach ($parameters as [$key, $value, $type]) {
            $parameter = new PlatformParameter();
            $parameter->setKey($key);
            $parameter->setValue($value);
            $parameter->setType($type);
            $parameter->setLabel(\ucfirst(\str_replace('_', ' ', $key)));

            $this->entityManager->persist($parameter);
        }

        $this->entityManager->flush();
    }
}
