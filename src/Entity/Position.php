<?php

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
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, DiscussionPost>
     */
    #[ORM\OneToMany(targetEntity: DiscussionPost::class, mappedBy: 'position')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $discussionPosts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->discussionPosts = new ArrayCollection();
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
}
