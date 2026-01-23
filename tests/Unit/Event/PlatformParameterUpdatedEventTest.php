<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Event;

use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;
use PHPUnit\Framework\TestCase;

class PlatformParameterUpdatedEventTest extends TestCase
{
    public function testCanCreateEventWithParameterAndValues(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('test_key');
        $parameter->setValue('new_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $event = new PlatformParameterUpdatedEvent($parameter, 'old_value', 'new_value');

        $this->assertSame($parameter, $event->getParameter());
        $this->assertSame('old_value', $event->getOldValue());
        $this->assertSame('new_value', $event->getNewValue());
    }
}
