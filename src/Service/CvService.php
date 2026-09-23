<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CurriculumVitae;
use App\Entity\CvLike;
use App\Entity\Position;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CvService
{
    public function __construct(
        private readonly CurriculumVitaeRepository $cvs,
        private readonly ProjectRepository $projects,
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
        private readonly CvLikeRepository $likes,
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
        foreach ($this->attributes->findBuiltIns() as $attribute) {
            $id = $attribute->getId();
            if ($id === null || AttributeValueHelper::isEmpty($values[$id] ?? null)) {
                return false;
            }
        }

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

    /**
     * @return list<CurriculumVitae>
     */
    public function listForProfile(User $owner, User $viewer): array
    {
        $cvs = $this->cvs->findByUser($owner);
        if ($viewer->isAdmin()) {
            return $cvs;
        }

        return $this->filterByOwnerAccess($cvs);
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function publishedVisibleTo(User $viewer): array
    {
        $cvs = $this->cvs->findPublishedForRecruiting();
        if ($viewer->isAdmin()) {
            return $cvs;
        }

        return $this->filterByOwnerAccess($cvs);
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function publishedVisibleForPosition(Position $position, User $viewer): array
    {
        $cvs = $this->cvs->findPublishedByPosition($position);
        if ($viewer->isAdmin()) {
            return $cvs;
        }

        return $this->filterByOwnerAccess($cvs);
    }

    public function toggleLike(User $recruiter, CurriculumVitae $cv): bool
    {
        $existing = $this->likes->findOneByRecruiterAndCv($recruiter, $cv);
        if ($existing !== null) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();

            return false;
        }

        $like = new CvLike();
        $like->setRecruiter($recruiter);
        $cv->addLike($like);
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param list<CurriculumVitae> $cvs
     * @return list<CurriculumVitae>
     */
    public function filterVisible(User $viewer, array $cvs): array
    {
        if ($viewer->isAdmin()) {
            return array_values($cvs);
        }

        return $this->filterByOwnerAccess($cvs);
    }

    /**
     * @param list<CurriculumVitae> $cvs
     * @return list<CurriculumVitae>
     */
    private function filterByOwnerAccess(array $cvs): array
    {
        $ownerIds = [];
        foreach ($cvs as $cv) {
            $id = $cv->getUser()?->getId();
            if ($id !== null) {
                $ownerIds[] = $id;
            }
        }

        $valuesByUser = $this->attributeValues->findIndexedForUserIds(array_values(array_unique($ownerIds)));

        return array_values(array_filter(
            $cvs,
            function (CurriculumVitae $cv) use ($valuesByUser): bool {
                $owner = $cv->getUser();
                $position = $cv->getPosition();
                $ownerId = $owner?->getId();
                if ($owner === null || $position === null || $ownerId === null) {
                    return false;
                }

                return $this->accessEvaluator->matchesValues($position, $valuesByUser[$ownerId] ?? []);
            },
        ));
    }
}
