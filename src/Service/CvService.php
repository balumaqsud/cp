<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CvService
{
    public function __construct(
        private readonly CurriculumVitaeRepository $cvs,
        private readonly ProjectRepository $projects,
        private readonly AttributeValueRepository $attributeValues,
        private readonly PositionAccessEvaluator $accessEvaluator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getOrCreate(User $user, Position $position): CurriculumVitae
    {
        if (!$this->accessEvaluator->canAccess($user, $position, false)) {
            throw new AccessDeniedException('You cannot create a CV for this position.');
        }

        $existing = $this->cvs->findOneByUserAndPosition($user, $position);
        if ($existing !== null) {
            return $existing;
        }

        $cv = new CurriculumVitae();
        $cv->setUser($user);
        $cv->setPosition($position);
        $cv->setStatus(CvStatus::Draft->value);

        $this->entityManager->persist($cv);
        $this->entityManager->flush();

        return $cv;
    }

    public function isComplete(CurriculumVitae $cv): bool
    {
        $user = $cv->getUser();
        $position = $cv->getPosition();
        if ($user === null || $position === null) {
            return false;
        }

        $values = $this->attributeValues->findIndexedByAttributeId($user);
        foreach ($position->getPositionAttributes() as $positionAttribute) {
            if (!$positionAttribute->isRequired()) {
                continue;
            }

            $attribute = $positionAttribute->getAttribute();
            $id = $attribute?->getId();
            if ($id === null || AttributeValueHelper::isEmpty($values[$id] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function publish(CurriculumVitae $cv): void
    {
        if (!$this->isComplete($cv)) {
            throw new \InvalidArgumentException('Fill every required field before publishing.');
        }

        $cv->setStatus(CvStatus::Published->value);
        $cv->touch();
        $this->entityManager->flush();
    }

    /**
     * @return list<Project>
     */
    public function relevantProjects(User $user, Position $position): array
    {
        $projects = $this->projects->findByOwner($user);
        $requiredTags = array_map(static fn (string $tag) => mb_strtolower($tag), $position->getProjectTags());

        if ($requiredTags !== []) {
            $projects = array_values(array_filter(
                $projects,
                static function (Project $project) use ($requiredTags): bool {
                    $tags = array_map(static fn (string $tag) => mb_strtolower($tag), $project->getTechnologyTags());

                    return array_intersect($requiredTags, $tags) !== [];
                },
            ));
        }

        $max = $position->getMaxProjects();
        if ($max !== null && $max >= 0) {
            return \array_slice($projects, 0, $max);
        }

        return $projects;
    }
}
