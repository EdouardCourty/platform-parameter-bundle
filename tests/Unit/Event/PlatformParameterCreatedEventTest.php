<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Unit\Event;

use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterCreatedEvent;
use PHPUnit\Framework\TestCase;

class PlatformParameterCreatedEventTest extends TestCase
{
    public function testCanCreateEventWithParameter(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('test_key');
        $parameter->setValue('test_value');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Test Label');

        $event = new PlatformParameterCreatedEvent($parameter);

        $this->assertSame($parameter, $event->getParameter());
    }
}
