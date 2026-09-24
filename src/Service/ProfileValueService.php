<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\AttributeValue;
use App\Entity\User;
use App\Enum\AttributeType;
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
        $this->upsertMany(
            $user,
            [[
                'attributeId' => (int) $attribute->getId(),
                'value' => $value,
                'version' => $expectedVersion,
            ]],
            [(int) $attribute->getId() => $attribute],
        );

        $row = $this->attributeValues->findOneByUserAndAttribute($user, $attribute);
        if ($row === null) {
            throw new \RuntimeException('Attribute value was not saved.');
        }

        return $row;
    }

    /**
     * @param list<array{attributeId: int, value: mixed, version?: int|null}> $items
     * @param array<int, Attribute> $attributesById
     * @return array<int, int> attribute id → new version
     */
    public function upsertMany(User $user, array $items, array $attributesById): array
    {
        $existing = $this->attributeValues->findEntitiesIndexedByAttributeId($user);
        $saved = [];

        foreach ($items as $item) {
            $attributeId = $item['attributeId'];
            $attribute = $attributesById[$attributeId] ?? null;
            if ($attribute === null) {
                continue;
            }

            $row = $existing[$attributeId] ?? null;
            $expected = $item['version'] ?? null;

            if ($row === null) {
                $row = new AttributeValue();
                $row->setUser($user);
                $row->setAttribute($attribute);
                $this->entityManager->persist($row);
                $existing[$attributeId] = $row;
            } elseif ($expected !== null && $row->getVersion() !== $expected) {
                throw OptimisticLockException::lockFailedVersionMismatch(
                    $row,
                    $expected,
                    $row->getVersion(),
                );
            }

            $row->setValue($this->normalize($attribute, $item['value'] ?? null));
            $saved[$attributeId] = $row;
        }

        $this->entityManager->flush();

        $versions = [];
        foreach ($saved as $attributeId => $row) {
            $versions[$attributeId] = $row->getVersion();
        }

        return $versions;
    }

    public function attachLibraryAttribute(User $user, Attribute $attribute): void
    {
        if ($attribute->isBuiltIn()) {
            return;
        }

        if ($this->attributeValues->findOneByUserAndAttribute($user, $attribute) !== null) {
            return;
        }

        $row = new AttributeValue();
        $row->setUser($user);
        $row->setAttribute($attribute);
        $row->setValue(null);
        $this->entityManager->persist($row);
        $this->entityManager->flush();
    }

    /**
     * @param list<int> $attributeIds
     */
    public function detachLibraryAttributes(User $user, array $attributeIds): void
    {
        $existing = $this->attributeValues->findEntitiesIndexedByAttributeId($user);
        foreach ($attributeIds as $id) {
            $row = $existing[$id] ?? null;
            if ($row === null || $row->getAttribute()?->isBuiltIn()) {
                continue;
            }

            $this->entityManager->remove($row);
        }

        $this->entityManager->flush();
    }

    private function normalize(Attribute $attribute, mixed $value): mixed
    {
        $type = AttributeType::tryFrom($attribute->getType()) ?? AttributeType::String;

        return match ($type) {
            AttributeType::Boolean => $this->asBool($value),
            AttributeType::Numeric => $this->asNumber($value),
            AttributeType::Period => $this->asPeriod($value),
            AttributeType::Date => $this->asDateString($value),
            AttributeType::Image => $this->asHttpsUrl($value),
            default => $value === '' ? null : $value,
        };
    }

    private function asHttpsUrl(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $url = trim((string) $value);
        if (!str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }

    private function asBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array($value, [1, '1', 'true', 'on'], true);
    }

    private function asNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return str_contains((string) $value, '.') ? (float) $value : (int) $value;
    }

    /**
     * @return array{from: ?string, to: ?string}|null
     */
    private function asPeriod(mixed $value): ?array
    {
        if (!\is_array($value)) {
            return null;
        }

        $from = $this->asDateString($value['from'] ?? null);
        $to = $this->asDateString($value['to'] ?? null);
        if ($from === null && $to === null) {
            return null;
        }

        return ['from' => $from, 'to' => $to];
    }

    private function asDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
