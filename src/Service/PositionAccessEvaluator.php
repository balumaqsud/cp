<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\User;
use App\Enum\AccessOperator;
use App\Repository\AttributeValueRepository;

final class PositionAccessEvaluator
{
    public function __construct(
        private readonly AttributeValueRepository $attributeValues,
    ) {
    }

    /**
     * Recruiter/admin bypass access filters. Guests only see public positions (caller must check).
     */
    public function canAccess(User $user, Position $position, bool $staffBypass): bool
    {
        if ($staffBypass && $user->isRecruiter()) {
            return true;
        }

        if ($position->isPublic()) {
            return true;
        }

        $rules = $position->getAccessRules();
        if ($rules->isEmpty()) {
            return true;
        }

        $values = $this->attributeValues->findIndexedByAttributeId($user);

        foreach ($rules as $rule) {
            if (!$this->matches($rule, $values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $valuesByAttributeId
     */
    private function matches(PositionAccessRule $rule, array $valuesByAttributeId): bool
    {
        $attribute = $rule->getAttribute();
        if ($attribute === null || $attribute->getId() === null) {
            return false;
        }

        $actual = $valuesByAttributeId[$attribute->getId()] ?? null;
        $expected = $rule->getCompareValue();
        $operator = AccessOperator::tryFrom($rule->getOperator()) ?? AccessOperator::Eq;

        if (AttributeValueHelper::isEmpty($actual)) {
            return false;
        }

        return match ($operator) {
            AccessOperator::Eq => $this->compare($actual, $expected) === 0,
            AccessOperator::Neq => $this->compare($actual, $expected) !== 0,
            AccessOperator::Gt => $this->compare($actual, $expected) > 0,
            AccessOperator::Gte => $this->compare($actual, $expected) >= 0,
            AccessOperator::Lt => $this->compare($actual, $expected) < 0,
            AccessOperator::Lte => $this->compare($actual, $expected) <= 0,
        };
    }

    private function compare(mixed $actual, mixed $expected): int
    {
        if (\is_bool($actual) || \is_bool($expected)) {
            return ((int) $this->asBool($actual)) <=> ((int) $this->asBool($expected));
        }

        if (is_numeric($actual) && is_numeric($expected)) {
            return (float) $actual <=> (float) $expected;
        }

        return strcmp((string) $actual, (string) $expected);
    }

    private function asBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array($value, [1, '1', 'true', 'on'], true);
    }
}
