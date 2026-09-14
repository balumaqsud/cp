<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PositionAttributeRepository::class)]
#[ORM\Table(name: 'position_attributes')]
#[ORM\UniqueConstraint(name: 'uniq_position_attributes_position_attribute', columns: ['position_id', 'attribute_id'])]
class PositionAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'positionAttributes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\ManyToOne(inversedBy: 'positionAttributes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Attribute $attribute = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $isRequired = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;

        return $this;
    }
}
