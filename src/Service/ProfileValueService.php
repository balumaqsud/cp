<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\AttributeValue;
use App\Entity\User;
use App\Repository\AttributeValueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

final class ProfileValueService
{
    public function __construct(
        private readonly AttributeValueRepository $attributeValues,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function upsert(User $user, Attribute $attribute, mixed $value, ?int $expectedVersion = null): AttributeValue
    {
        $row = $this->attributeValues->findOneByUserAndAttribute($user, $attribute);

        if ($row === null) {
            $row = new AttributeValue();
            $row->setUser($user);
            $row->setAttribute($attribute);
            $this->entityManager->persist($row);
        } elseif ($expectedVersion !== null && $row->getVersion() !== $expectedVersion) {
            throw OptimisticLockException::lockFailedVersionMismatch(
                $row,
                $expectedVersion,
                $row->getVersion(),
            );
        }

        $row->setValue($this->normalize($value));
        $this->entityManager->flush();

        return $row;
    }

    /**
     * @param list<array{attributeId: int, value: mixed, version?: int|null}> $items
     * @param array<int, Attribute> $attributesById
     */
    public function upsertMany(User $user, array $items, array $attributesById): void
    {
        foreach ($items as $item) {
            $attribute = $attributesById[$item['attributeId']] ?? null;
            if ($attribute === null) {
                continue;
            }

            $this->upsert(
                $user,
                $attribute,
                $item['value'] ?? null,
                isset($item['version']) ? (int) $item['version'] : null,
            );
        }
    }

    private function normalize(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }
}
