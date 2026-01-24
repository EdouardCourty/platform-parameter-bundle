<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'platform_parameter')]
#[ORM\Index(name: 'idx_platform_parameter_key', columns: ['key'])]
#[ORM\UniqueConstraint(name: 'uniq_platform_parameter_key', columns: ['key'])]
class PlatformParameter extends AbstractPlatformParameter
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    public function __construct()
    {
        parent::__construct();
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
