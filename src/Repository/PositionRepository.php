<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Position;
use App\Enum\CvStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    /**
     * @return list<Position>
     */
    public function findPublic(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = true')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = true')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findPopular(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.cvs', 'cv', 'WITH', 'cv.status = :published')
            ->setParameter('published', CvStatus::Published->value)
            ->andWhere('p.isPublic = true')
            ->groupBy('p.id')
            ->orderBy('COUNT(cv.id)', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findAllManaged(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function searchFullText(string $query): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT id
            FROM positions
            WHERE to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(short_description, ''))
                  @@ plainto_tsquery('simple', :q)
            ORDER BY updated_at DESC
            SQL,
            ['q' => $query],
        );

        return $this->findByIds($ids);
    }

    /**
     * @param list<int|string> $ids
     * @return list<Position>
     */
    private function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<Position> $rows */
        $rows = $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->getId()] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(int) $id])) {
                $ordered[] = $byId[(int) $id];
            }
        }

        return $ordered;
    }
}
