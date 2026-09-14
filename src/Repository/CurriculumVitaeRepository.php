<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CurriculumVitae>
 */
class CurriculumVitaeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurriculumVitae::class);
    }

    public function findOneByUserAndPosition(User $user, Position $position): ?CurriculumVitae
    {
        return $this->findOneBy([
            'user' => $user,
            'position' => $position,
        ]);
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function findPublishedByPosition(Position $position): array
    {
        return $this->findBy([
            'position' => $position,
            'status' => 'published',
        ]);
    }
}
