<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionAccessRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PositionAccessRuleRepository::class)]
#[ORM\Table(name: 'position_access_rules')]
class PositionAccessRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accessRules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Attribute $attribute = null;

    #[ORM\Column(length: 20)]
    private string $operator;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private mixed $compareValue = null;

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

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function getCompareValue(): mixed
    {
        return $this->compareValue;
    }

    public function setCompareValue(mixed $compareValue): static
    {
        $this->compareValue = $compareValue;

        return $this;
    }
}
