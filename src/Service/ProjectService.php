<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Project $project): void
    {
        $project->touch();
        if ($project->getId() === null) {
            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<int> $ids
     */
    public function deleteForOwner(User $owner, array $ids): int
    {
        $selected = $this->projects->findByOwnerAndIds($owner, $ids);
        foreach ($selected as $project) {
            $this->entityManager->remove($project);
        }

        if ($selected !== []) {
            $this->entityManager->flush();
        }

        return \count($selected);
    }
}
