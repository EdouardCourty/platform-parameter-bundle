<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Event;

use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;

/**
 * Event dispatched when a platform parameter is created.
 */
final readonly class PlatformParameterCreatedEvent
{
    public function __construct(
        private AbstractPlatformParameter $parameter,
    ) {
    }

    public function getParameter(): AbstractPlatformParameter
    {
        return $this->parameter;
    }
}
