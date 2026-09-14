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
}
