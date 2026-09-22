<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\PositionAttribute;
use Doctrine\ORM\EntityManagerInterface;

final class PositionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function duplicate(Position $source): Position
    {
        $copy = new Position();
        $copy->setTitle($source->getTitle().' (copy)');
        $copy->setShortDescription($source->getShortDescription());
        $copy->setIsPublic($source->isPublic());
        $copy->setProjectTags($source->getProjectTags());
        $copy->setMaxProjects($source->getMaxProjects());
        $copy->setCompany($source->getCompany());
        $copy->setLevel($source->getLevel());

        foreach ($source->getPositionAttributes() as $positionAttribute) {
            $clone = new PositionAttribute();
            $clone->setAttribute($positionAttribute->getAttribute());
            $clone->setSortOrder($positionAttribute->getSortOrder());
            $clone->setIsRequired($positionAttribute->isRequired());
            $copy->addPositionAttribute($clone);
        }

        foreach ($source->getAccessRules() as $rule) {
            $clone = new PositionAccessRule();
            $clone->setAttribute($rule->getAttribute());
            $clone->setOperator($rule->getOperator());
            $clone->setCompareValue($rule->getCompareValue());
            $copy->addAccessRule($clone);
        }

        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }

    /**
     * @param list<int> $attributeIds
     * @param array<int, bool> $requiredById
     */
    public function syncAttributes(Position $position, array $attributeIds, array $requiredById, array $attributesById): void
    {
        foreach ($position->getPositionAttributes()->toArray() as $existing) {
            $position->removePositionAttribute($existing);
        }

        $sort = 0;
        foreach ($attributeIds as $id) {
            $attribute = $attributesById[$id] ?? null;
            if ($attribute === null) {
                continue;
            }

            $row = new PositionAttribute();
            $row->setAttribute($attribute);
            $row->setSortOrder($sort++);
            $row->setIsRequired($requiredById[$id] ?? true);
            $position->addPositionAttribute($row);
        }
    }

    /**
     * @param list<array{attributeId?: int, operator?: string, compareValue?: mixed}> $rules
     */
    public function syncAccessRules(Position $position, array $rules, array $attributesById): void
    {
        foreach ($position->getAccessRules()->toArray() as $existing) {
            $position->removeAccessRule($existing);
        }

        foreach ($rules as $ruleData) {
            $attributeId = (int) ($ruleData['attributeId'] ?? 0);
            $attribute = $attributesById[$attributeId] ?? null;
            $operator = (string) ($ruleData['operator'] ?? '');
            if ($attribute === null || $operator === '') {
                continue;
            }

            $row = new PositionAccessRule();
            $row->setAttribute($attribute);
            $row->setOperator($operator);
            $value = $ruleData['compareValue'] ?? null;
            if (is_numeric($value) && !str_contains((string) $value, ' ')) {
                $value = str_contains((string) $value, '.') ? (float) $value : (int) $value;
            }
            if (\in_array($value, ['true', 'false'], true)) {
                $value = $value === 'true';
            }
            $row->setCompareValue($value === '' ? null : $value);
            $position->addAccessRule($row);
        }
    }
}
