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
}
