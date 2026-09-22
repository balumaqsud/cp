<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Attribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attribute>
 */
class AttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attribute::class);
    }

    /**
     * @return list<Attribute>
     */
    public function search(?string $prefix, ?int $categoryId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')->addSelect('c')
            ->orderBy('a.isBuiltIn', 'DESC')
            ->addOrderBy('a.name', 'ASC');

        if ($prefix !== null && $prefix !== '') {
            $qb->andWhere('LOWER(a.name) LIKE :prefix')
                ->setParameter('prefix', mb_strtolower($prefix).'%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Attribute>
     */
    public function findBuiltIns(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isBuiltIn = true')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Attribute>
     */
    public function findLibrary(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')->addSelect('c')
            ->andWhere('a.isBuiltIn = false')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Attribute>
     */
    public function findIndexedById(): array
    {
        $indexed = [];
        foreach ($this->findAll() as $attribute) {
            if ($attribute->getId() !== null) {
                $indexed[$attribute->getId()] = $attribute;
            }
        }

        return $indexed;
    }

    public function findOneByName(string $name): ?Attribute
    {
        return $this->findOneBy(['name' => $name]);
    }
}
