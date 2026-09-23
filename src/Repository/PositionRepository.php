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
    public function findLatestManaged(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findByTag(string $tag): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT id
            FROM positions
            WHERE EXISTS (
                SELECT 1
                FROM jsonb_array_elements_text(project_tags::jsonb) t
                WHERE lower(t) = :tag
            )
            ORDER BY updated_at DESC
            SQL,
            ['tag' => mb_strtolower($tag)],
        );

        return $this->findByIds($ids);
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
            WHERE to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(short_description, '') || ' ' || coalesce(project_tags::text, ''))
                  @@ plainto_tsquery('simple', :q)
            ORDER BY updated_at DESC
            SQL,
            ['q' => $query],
        );

        return $this->findByIds($ids);
    }

    /**
     * @return list<Position>
     */
    public function findAllWithAccessGraph(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'a')->addSelect('a')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithTemplate(int $id): ?Position
    {
        $position = $this->createQueryBuilder('p')
            ->leftJoin('p.positionAttributes', 'pa')->addSelect('pa')
            ->leftJoin('pa.attribute', 'attr')->addSelect('attr')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($position === null) {
            return null;
        }

        $this->createQueryBuilder('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'ruleAttr')->addSelect('ruleAttr')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $position;
    }

    /**
     * @param list<int|string> $ids
     * @return list<Position>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<Position> $rows */
        $rows = $this->createQueryBuilder('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'a')->addSelect('a')
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
