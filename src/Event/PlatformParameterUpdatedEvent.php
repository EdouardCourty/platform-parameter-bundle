<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Event;

use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;

/**
 * Event dispatched when a platform parameter is updated.
 */
final readonly class PlatformParameterUpdatedEvent
{
    public function __construct(
        private AbstractPlatformParameter $parameter,
        private string $oldValue,
        private string $newValue,
    ) {
    }

    public function getParameter(): AbstractPlatformParameter
    {
        return $this->parameter;
    }

    public function getOldValue(): string
    {
        return $this->oldValue;
    }

    public function getNewValue(): string
    {
        return $this->newValue;
    }
}
