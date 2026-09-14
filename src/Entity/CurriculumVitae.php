<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CurriculumVitaeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Values are read/written via AttributeValue for (user, attribute) — not copied onto the CV. */
#[ORM\Entity(repositoryClass: CurriculumVitaeRepository::class)]
#[ORM\Table(name: 'cvs')]
#[ORM\UniqueConstraint(name: 'uniq_cvs_user_position', columns: ['user_id', 'position_id'])]
class CurriculumVitae
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\Column(length: 20)]
    private string $status = 'draft';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    /**
     * @var Collection<int, CvLike>
     */
    #[ORM\OneToMany(targetEntity: CvLike::class, mappedBy: 'curriculumVitae')]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return Collection<int, CvLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(CvLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setCurriculumVitae($this);
        }

        return $this;
    }

    public function removeLike(CvLike $like): static
    {
        if ($this->likes->removeElement($like) && $like->getCurriculumVitae() === $this) {
            $like->setCurriculumVitae(null);
        }

        return $this;
    }
}
