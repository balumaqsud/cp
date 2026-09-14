<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table(name: 'positions')]
class Position
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column]
    private bool $isPublic = true;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $projectTags = [];

    #[ORM\Column(nullable: true)]
    private ?int $maxProjects = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $level = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, DiscussionPost>
     */
    #[ORM\OneToMany(targetEntity: DiscussionPost::class, mappedBy: 'position')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $discussionPosts;

    /**
     * @var Collection<int, PositionAttribute>
     */
    #[ORM\OneToMany(targetEntity: PositionAttribute::class, mappedBy: 'position')]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $positionAttributes;

    /**
     * @var Collection<int, PositionAccessRule>
     */
    #[ORM\OneToMany(targetEntity: PositionAccessRule::class, mappedBy: 'position')]
    private Collection $accessRules;

    /**
     * @var Collection<int, CurriculumVitae>
     */
    #[ORM\OneToMany(targetEntity: CurriculumVitae::class, mappedBy: 'position')]
    private Collection $cvs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->discussionPosts = new ArrayCollection();
        $this->positionAttributes = new ArrayCollection();
        $this->accessRules = new ArrayCollection();
        $this->cvs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /** @return list<string> */
    public function getProjectTags(): array
    {
        return $this->projectTags;
    }

    /** @param list<string> $projectTags */
    public function setProjectTags(array $projectTags): static
    {
        $this->projectTags = $projectTags;

        return $this;
    }

    public function getMaxProjects(): ?int
    {
        return $this->maxProjects;
    }

    public function setMaxProjects(?int $maxProjects): static
    {
        $this->maxProjects = $maxProjects;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, DiscussionPost>
     */
    public function getDiscussionPosts(): Collection
    {
        return $this->discussionPosts;
    }

    public function addDiscussionPost(DiscussionPost $discussionPost): static
    {
        if (!$this->discussionPosts->contains($discussionPost)) {
            $this->discussionPosts->add($discussionPost);
            $discussionPost->setPosition($this);
        }

        return $this;
    }

    public function removeDiscussionPost(DiscussionPost $discussionPost): static
    {
        if ($this->discussionPosts->removeElement($discussionPost) && $discussionPost->getPosition() === $this) {
            $discussionPost->setPosition(null);
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
            $positionAttribute->setPosition($this);
        }

        return $this;
    }

    public function removePositionAttribute(PositionAttribute $positionAttribute): static
    {
        if ($this->positionAttributes->removeElement($positionAttribute) && $positionAttribute->getPosition() === $this) {
            $positionAttribute->setPosition(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PositionAccessRule>
     */
    public function getAccessRules(): Collection
    {
        return $this->accessRules;
    }

    public function addAccessRule(PositionAccessRule $accessRule): static
    {
        if (!$this->accessRules->contains($accessRule)) {
            $this->accessRules->add($accessRule);
            $accessRule->setPosition($this);
        }

        return $this;
    }

    public function removeAccessRule(PositionAccessRule $accessRule): static
    {
        if ($this->accessRules->removeElement($accessRule) && $accessRule->getPosition() === $this) {
            $accessRule->setPosition(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CurriculumVitae>
     */
    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    public function addCv(CurriculumVitae $cv): static
    {
        if (!$this->cvs->contains($cv)) {
            $this->cvs->add($cv);
            $cv->setPosition($this);
        }

        return $this;
    }

    public function removeCv(CurriculumVitae $cv): static
    {
        if ($this->cvs->removeElement($cv) && $cv->getPosition() === $this) {
            $cv->setPosition(null);
        }

        return $this;
    }
}
