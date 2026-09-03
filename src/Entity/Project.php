<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;


#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name:'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

  #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type:'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

     #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName():string {
        return $this->name;
    }
    public function setName(string $name): static {
        $this-> name = $name;
        return $this;
    }
    public function getDescription() {
        return $this->description;
    }
    public function setDescription(?string $description){
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable{
        return $this->createdAt;
    }
    public function setCreatedAt(?\DateTimeImmutable $createdAt): static {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable {
        return $this->updatedAt;
    }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOwner(): ?User {
        return $this->owner;
    }
    public function setOwner(?User $owner) : static {
        $this->owner - $owner;
        return $this;
    }
}
