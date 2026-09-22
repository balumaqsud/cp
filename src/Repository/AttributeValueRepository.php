<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Attribute;
use App\Entity\AttributeValue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AttributeValue>
 */
class AttributeValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributeValue::class);
    }

    public function findOneByUserAndAttribute(User $user, Attribute $attribute): ?AttributeValue
    {
        return $this->findOneBy([
            'user' => $user,
            'attribute' => $attribute,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    public function findIndexedByAttributeId(User $user): array
    {
        $rows = $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($rows as $row) {
            $id = $row->getAttribute()?->getId();
            if ($id !== null) {
                $indexed[$id] = $row->getValue();
            }
        }

        return $indexed;
    }

    /**
     * @return array<int, AttributeValue>
     */
    public function findEntitiesIndexedByAttributeId(User $user): array
    {
        $rows = $this->createQueryBuilder('v')
            ->innerJoin('v.attribute', 'a')->addSelect('a')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($rows as $row) {
            $id = $row->getAttribute()?->getId();
            if ($id !== null) {
                $indexed[$id] = $row;
            }
        }

        return $indexed;
    }
}
