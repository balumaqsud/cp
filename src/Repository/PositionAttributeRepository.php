<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Position;
use App\Entity\PositionAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PositionAttribute>
 */
class PositionAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PositionAttribute::class);
    }

    /**
     * @return list<PositionAttribute>
     */
    public function findByPositionOrdered(Position $position): array
    {
        return $this->findBy(
            ['position' => $position],
            ['sortOrder' => 'ASC']
        );
    }
}
