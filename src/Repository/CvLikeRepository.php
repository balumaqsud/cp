<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CurriculumVitae;
use App\Entity\CvLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CvLike>
 */
class CvLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CvLike::class);
    }

    public function findOneByRecruiterAndCv(User $recruiter, CurriculumVitae $curriculumVitae): ?CvLike
    {
        return $this->findOneBy([
            'recruiter' => $recruiter,
            'curriculumVitae' => $curriculumVitae,
        ]);
    }

    public function countByCv(CurriculumVitae $curriculumVitae): int
    {
        return $this->count(['curriculumVitae' => $curriculumVitae]);
    }

    /**
     * @param list<int> $cvIds
     * @return array<int, int>
     */
    public function countIndexedByCvIds(array $cvIds): array
    {
        if ($cvIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('l')
            ->select('cv.id AS cvId, COUNT(l.id) AS cnt')
            ->innerJoin('l.curriculumVitae', 'cv')
            ->andWhere('cv.id IN (:ids)')
            ->setParameter('ids', $cvIds)
            ->groupBy('cv.id')
            ->getQuery()
            ->getScalarResult();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['cvId']] = (int) $row['cnt'];
        }

        return $indexed;
    }
}
