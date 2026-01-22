<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'platform_parameter')]
#[ORM\Index(name: 'idx_platform_parameter_key', columns: ['key'])]
#[ORM\UniqueConstraint(name: 'uniq_platform_parameter_key', columns: ['key'])]
class PlatformParameter extends AbstractPlatformParameter
{
}
