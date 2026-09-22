<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\User;
use App\Enum\CvStatus;
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
        return $this->createQueryBuilder('cv')
            ->innerJoin('cv.user', 'u')->addSelect('u')
            ->andWhere('cv.position = :position')
            ->andWhere('cv.status = :status')
            ->setParameter('position', $position)
            ->setParameter('status', CvStatus::Published->value)
            ->orderBy('cv.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $ids
     * @return list<CurriculumVitae>
     */
    public function findByUserAndIds(User $user, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<CurriculumVitae> $rows */
        $rows = $this->createQueryBuilder('cv')
            ->andWhere('cv.user = :user')
            ->andWhere('cv.id IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('cv')
            ->innerJoin('cv.position', 'p')->addSelect('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'ra')->addSelect('ra')
            ->andWhere('cv.user = :user')
            ->setParameter('user', $user)
            ->orderBy('cv.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function findPublishedForRecruiting(): array
    {
        return $this->createQueryBuilder('cv')
            ->innerJoin('cv.user', 'u')->addSelect('u')
            ->innerJoin('cv.position', 'p')->addSelect('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'ra')->addSelect('ra')
            ->andWhere('cv.status = :status')
            ->setParameter('status', CvStatus::Published->value)
            ->orderBy('cv.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithOwnerAndPosition(int $id): ?CurriculumVitae
    {
        return $this->createQueryBuilder('cv')
            ->innerJoin('cv.user', 'u')->addSelect('u')
            ->innerJoin('cv.position', 'p')->addSelect('p')
            ->andWhere('cv.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countPublishedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('cv')
            ->select('COUNT(cv.id)')
            ->andWhere('cv.status = :status')
            ->andWhere('cv.createdAt >= :since')
            ->setParameter('status', CvStatus::Published->value)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublished(): int
    {
        return $this->count(['status' => CvStatus::Published->value]);
    }

    /**
     * @return list<CurriculumVitae>
     */
    public function searchPublishedFullText(string $query): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT cv.id
            FROM cvs cv
            INNER JOIN users u ON u.id = cv.user_id
            INNER JOIN positions p ON p.id = cv.position_id
            WHERE cv.status = :status
              AND to_tsvector('simple', coalesce(u.name, '') || ' ' || coalesce(p.title, '') || ' ' || coalesce(p.short_description, ''))
                  @@ plainto_tsquery('simple', :q)
            ORDER BY cv.updated_at DESC
            SQL,
            ['status' => CvStatus::Published->value, 'q' => $query],
        );

        if ($ids === []) {
            return [];
        }

        /** @var list<CurriculumVitae> $rows */
        $rows = $this->createQueryBuilder('cv')
            ->innerJoin('cv.user', 'u')->addSelect('u')
            ->innerJoin('cv.position', 'p')->addSelect('p')
            ->andWhere('cv.id IN (:ids)')
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
