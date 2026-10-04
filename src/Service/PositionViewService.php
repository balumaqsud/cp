<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\Position;
use App\Enum\AccessOperator;
use App\Repository\AttributeRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProjectRepository;

final class PositionViewService
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly CategoryRepository $categories,
        private readonly ProjectRepository $projects,
        private readonly RecentAttributeStore $recentAttributes,
    ) {
    }

    /**
     * @param list<int> $selectedIds
     * @param array<int, bool> $requiredById
     * @param array<mixed> $postedRules
     * @return array<string, mixed>
     */
    public function formContext(
        Position $position,
        bool $submitted,
        array $selectedIds,
        array $requiredById,
        array $postedRules,
        string $prefix,
        int $categoryId,
    ): array {
        $prefix = trim($prefix);

        if (!$submitted) {
            foreach ($position->getPositionAttributes() as $positionAttribute) {
                $id = $positionAttribute->getAttribute()?->getId();
                if ($id === null) {
                    continue;
                }
                $selectedIds[] = $id;
                $requiredById[$id] = $positionAttribute->isRequired();
            }
            $selectedIds = array_values(array_unique($selectedIds));
        }

        $filterActive = $prefix !== '' || $categoryId > 0;
        $recent = $this->orderedAttributes($this->recentAttributes->ids());
        $recentIds = $this->attributeIds($recent);

        $filtered = [];
        if ($filterActive) {
            $matched = $this->attributes->search(
                $prefix !== '' ? $prefix : null,
                $categoryId > 0 ? $categoryId : null,
            );
            $filtered = array_values(array_filter(
                $matched,
                static fn (Attribute $attribute): bool => !\in_array($attribute->getId(), $recentIds, true),
            ));
        }

        $rules = $submitted ? $postedRules : $this->ruleRows($position);

        return [
            'ruleAttributes' => $this->ruleAttributes($recent, $filtered, $selectedIds, $this->ruleAttributeIds($rules)),
            'filteredLibrary' => $filtered,
            'recentAttributes' => $recent,
            'filterActive' => $filterActive,
            'categories' => $this->categories->findAllOrdered(),
            'prefix' => $prefix,
            'categoryId' => $categoryId > 0 ? $categoryId : null,
            'selectedIds' => $selectedIds,
            'requiredById' => $requiredById,
            'rules' => $rules,
            'operators' => AccessOperator::choices(),
            'tagSuggestions' => $this->projects->findDistinctTags(),
        ];
    }

    /**
     * @param list<int> $ids
     * @return list<Attribute>
     */
    private function orderedAttributes(array $ids): array
    {
        $byId = [];
        foreach ($this->attributes->findByIds($ids) as $attribute) {
            if ($attribute->getId() !== null) {
                $byId[$attribute->getId()] = $attribute;
            }
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * @param list<Attribute> $recent
     * @param list<Attribute> $filtered
     * @param list<int> $selectedIds
     * @param list<int> $ruleIds
     * @return list<Attribute>
     */
    private function ruleAttributes(array $recent, array $filtered, array $selectedIds, array $ruleIds): array
    {
        $picker = [];
        foreach ([...$recent, ...$filtered] as $attribute) {
            if ($attribute->getId() !== null) {
                $picker[$attribute->getId()] = $attribute;
            }
        }

        $missing = [];
        foreach ([...$selectedIds, ...$ruleIds] as $id) {
            if (!isset($picker[$id])) {
                $missing[] = $id;
            }
        }

        foreach ($this->orderedAttributes($missing) as $attribute) {
            if ($attribute->getId() !== null) {
                $picker[$attribute->getId()] = $attribute;
            }
        }

        return array_values($picker);
    }

    /**
     * @param list<Attribute> $attributes
     * @return list<int>
     */
    private function attributeIds(array $attributes): array
    {
        $ids = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getId() !== null) {
                $ids[] = $attribute->getId();
            }
        }

        return $ids;
    }

    /**
     * @param array<mixed> $rules
     * @return list<int>
     */
    private function ruleAttributeIds(array $rules): array
    {
        $ids = [];
        foreach ($rules as $rule) {
            if (!\is_array($rule)) {
                continue;
            }

            $id = (int) ($rule['attributeId'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<array{attributeId: int|string, operator: string, compareValue: mixed}>
     */
    private function ruleRows(Position $position): array
    {
        $rows = [];
        foreach ($position->getAccessRules() as $rule) {
            $rows[] = [
                'attributeId' => $rule->getAttribute()?->getId() ?? '',
                'operator' => $rule->getOperator(),
                'compareValue' => $rule->getCompareValue(),
            ];
        }

        return $rows;
    }
}
