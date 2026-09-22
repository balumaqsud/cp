<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttributeRepository::class)]
#[ORM\Table(name: 'attributes')]
#[UniqueEntity(fields: ['name'], message: 'An attribute with this name already exists.')]
class Attribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column]
    private bool $isBuiltIn = false;

    #[ORM\Column(type: Types::JSON)]
    private array $options = [];

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?Category $category = null;

    /**
     * @var Collection<int, AttributeValue>
     */
    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: AttributeValue::class)]
    private Collection $attributeValues;

    /**
     * @var Collection<int, PositionAttribute>
     */
    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: PositionAttribute::class)]
    private Collection $positionAttributes;

    public function __construct()
    {
        $this->attributeValues = new ArrayCollection();
        $this->positionAttributes = new ArrayCollection();
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, AttributeValue>
     */
    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    public function addAttributeValue(AttributeValue $attributeValue): static
    {
        if (!$this->attributeValues->contains($attributeValue)) {
            $this->attributeValues->add($attributeValue);
            $attributeValue->setAttribute($this);
        }

        return $this;
    }

    public function removeAttributeValue(AttributeValue $attributeValue): static
    {
        if ($this->attributeValues->removeElement($attributeValue) && $attributeValue->getAttribute() === $this) {
            $attributeValue->setAttribute(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PositionAttribute>
     */
    public function getPositionAttributes(): Collection
    {
        return $this->positionAttributes;
    }

    public function addPositionAttribute(PositionAttribute $positionAttribute): static
    {
        if (!$this->positionAttributes->contains($positionAttribute)) {
            $this->positionAttributes->add($positionAttribute);
            $positionAttribute->setAttribute($this);
        }

        return $this;
    }

    public function removePositionAttribute(PositionAttribute $positionAttribute): static
    {
        if ($this->positionAttributes->removeElement($positionAttribute) && $positionAttribute->getAttribute() === $this) {
            $positionAttribute->setAttribute(null);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isBuiltIn(): bool
    {
        return $this->isBuiltIn;
    }

    public function setIsBuiltIn(bool $isBuiltIn): static
    {
        $this->isBuiltIn = $isBuiltIn;

        return $this;
    }

    public function isDeletable(): bool
    {
        return !$this->isBuiltIn;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
