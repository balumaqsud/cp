<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_CANDIDATE = 'ROLE_CANDIDATE';
    public const ROLE_RECRUITER = 'ROLE_RECRUITER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private bool $isBlocked = false;

    #[ORM\Column(length: 10)]
    private string $locale = 'en';

    #[ORM\Column(length: 20)]
    private string $theme = 'light';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'owner')]
    private Collection $projects;

    /**
     * @var Collection<int, DiscussionPost>
     */
    #[ORM\OneToMany(targetEntity: DiscussionPost::class, mappedBy: 'author')]
    private Collection $discussionPosts;

    /**
     * @var Collection<int, AttributeValue>
     */
    #[ORM\OneToMany(targetEntity: AttributeValue::class, mappedBy: 'user')]
    private Collection $attributeValues;

    /**
     * @var Collection<int, CurriculumVitae>
     */
    #[ORM\OneToMany(targetEntity: CurriculumVitae::class, mappedBy: 'user')]
    private Collection $cvs;

    /**
     * @var Collection<int, CvLike>
     */
    #[ORM\OneToMany(targetEntity: CvLike::class, mappedBy: 'recruiter')]
    private Collection $cvLikes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->projects = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->cvLikes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasAssignedRole(): bool
    {
        foreach ($this->getRoles() as $role) {
            if (\in_array($role, [self::ROLE_CANDIDATE, self::ROLE_RECRUITER, self::ROLE_ADMIN], true)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->getRoles(), true);
    }

    public function isRecruiter(): bool
    {
        return $this->isAdmin() || \in_array(self::ROLE_RECRUITER, $this->getRoles(), true);
    }

    public function isCandidate(): bool
    {
        return $this->isAdmin() || \in_array(self::ROLE_CANDIDATE, $this->getRoles(), true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
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

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setOwner($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project) && $project->getOwner() === $this) {
            $project->setOwner(null);
        }

        return $this;
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
            $discussionPost->setAuthor($this);
        }

        return $this;
    }

    public function removeDiscussionPost(DiscussionPost $discussionPost): static
    {
        if ($this->discussionPosts->removeElement($discussionPost) && $discussionPost->getAuthor() === $this) {
            $discussionPost->setAuthor(null);
        }

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
            $attributeValue->setUser($this);
        }

        return $this;
    }

    public function removeAttributeValue(AttributeValue $attributeValue): static
    {
        if ($this->attributeValues->removeElement($attributeValue) && $attributeValue->getUser() === $this) {
            $attributeValue->setUser(null);
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
            $cv->setUser($this);
        }

        return $this;
    }

    public function removeCv(CurriculumVitae $cv): static
    {
        if ($this->cvs->removeElement($cv) && $cv->getUser() === $this) {
            $cv->setUser(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CvLike>
     */
    public function getCvLikes(): Collection
    {
        return $this->cvLikes;
    }

    public function addCvLike(CvLike $cvLike): static
    {
        if (!$this->cvLikes->contains($cvLike)) {
            $this->cvLikes->add($cvLike);
            $cvLike->setRecruiter($this);
        }

        return $this;
    }

    public function removeCvLike(CvLike $cvLike): static
    {
        if ($this->cvLikes->removeElement($cvLike) && $cvLike->getRecruiter() === $this) {
            $cvLike->setRecruiter(null);
        }

        return $this;
    }
}
