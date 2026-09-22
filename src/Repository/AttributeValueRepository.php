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
            ->innerJoin('v.attribute', 'a')->addSelect('a')
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
     * @param list<int> $userIds
     * @return array<int, array<int, mixed>> user id → attribute id → value
     */
    public function findIndexedForUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->innerJoin('v.attribute', 'a')->addSelect('a')
            ->innerJoin('v.user', 'u')->addSelect('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $userIds)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($rows as $row) {
            $userId = $row->getUser()?->getId();
            $attributeId = $row->getAttribute()?->getId();
            if ($userId === null || $attributeId === null) {
                continue;
            }
            $indexed[$userId][$attributeId] = $row->getValue();
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
