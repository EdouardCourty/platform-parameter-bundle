<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Custom Platform Parameter entity for testing extensibility.
 *
 * Adds custom fields (category, sortOrder, icon) to demonstrate
 * how users can extend the base entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_platform_parameter')]
class CustomPlatformParameter extends AbstractPlatformParameter
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sortOrder = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $icon = null;

    public function __construct()
    {
        parent::__construct();
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}
