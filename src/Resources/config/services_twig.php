<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ecourty\PlatformParameterBundle\Twig\PlatformParameterExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PlatformParameterExtension::class)
        ->autowire()
        ->tag('twig.extension');
};
