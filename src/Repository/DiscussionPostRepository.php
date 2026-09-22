<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscussionPost;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DiscussionPost>
 */
class DiscussionPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscussionPost::class);
    }

    /**
     * @return list<DiscussionPost>
     */
    public function findByPositionChronological(Position $position): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')->addSelect('author')
            ->andWhere('post.position = :position')
            ->setParameter('position', $position)
            ->orderBy('post.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
