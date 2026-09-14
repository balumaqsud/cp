<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CvLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CvLikeRepository::class)]
#[ORM\Table(name: 'cv_likes')]
#[ORM\UniqueConstraint(name: 'uniq_cv_likes_recruiter_cv', columns: ['recruiter_id', 'curriculum_vitae_id'])]
class CvLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cvLikes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $recruiter = null;

    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CurriculumVitae $curriculumVitae = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }

    public function setRecruiter(?User $recruiter): static
    {
        $this->recruiter = $recruiter;

        return $this;
    }

    public function getCurriculumVitae(): ?CurriculumVitae
    {
        return $this->curriculumVitae;
    }

    public function setCurriculumVitae(?CurriculumVitae $curriculumVitae): static
    {
        $this->curriculumVitae = $curriculumVitae;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
