<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Position;
use App\Entity\PositionAccessRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PositionAccessRule>
 */
class PositionAccessRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PositionAccessRule::class);
    }

    /**
     * @return list<PositionAccessRule>
     */
    public function findByPosition(Position $position): array
    {
        return $this->findBy(['position' => $position]);
    }
}
