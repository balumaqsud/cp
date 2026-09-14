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

    public function findOneByName(string $name): ?Attribute
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @return list<Attribute>
     */
    public function findBuiltIn(): array
    {
        return $this->findBy(['isBuiltIn' => true], ['name' => 'ASC']);
    }
}
