<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ecourty\PlatformParameterBundle\Command\GetCommand;
use Ecourty\PlatformParameterBundle\Command\ListCommand;
use Ecourty\PlatformParameterBundle\Command\SetCommand;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Service\PlatformParameterProvider;
use Ecourty\PlatformParameterBundle\Service\PlatformParameterWriter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Ecourty\\PlatformParameterBundle\\', '../../*')
        ->exclude('../../{Controller,Entity,Enum,Exception,EventListener,Resources,Twig}');

    $services->set(PlatformParameterProviderInterface::class, PlatformParameterProvider::class)
        ->public();

    $services->set(PlatformParameterWriterInterface::class, PlatformParameterWriter::class)
        ->public();

    // Note: PlatformParameterProvider is configured programmatically in PlatformParameterExtension
    // to inject the correct cache adapter with dynamic configuration

    // Commands configuration
    $entityClassParam = '%platform_parameter.entity_class%';

    $services->set(ListCommand::class)
        ->arg('$entityClass', $entityClassParam);

    $services->set(GetCommand::class)
        ->arg('$entityClass', $entityClassParam);

    $services->set(SetCommand::class)
        ->arg('$entityClass', $entityClassParam);
};
