<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Enum\AttributeType;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;

final class PositionAggregateService
{
    public function __construct(
        private readonly CurriculumVitaeRepository $cvs,
        private readonly AttributeValueRepository $values,
    ) {
    }

    /**
     * @return array{title: string, publishedCvCount: int, attributes: list<array{title: string, type: string, result: array<string, mixed>}>}
     */
    public function build(Position $position): array
    {
        $published = $this->cvs->findPublishedByPosition($position);
        $userIds = [];
        foreach ($published as $cv) {
            $userId = $cv->getUser()?->getId();
            if ($userId !== null) {
                $userIds[] = $userId;
            }
        }

        $byUser = $userIds === [] ? [] : $this->values->findIndexedForUserIds($userIds);

        $attributes = [];
        foreach ($position->getPositionAttributes() as $positionAttribute) {
            $attribute = $positionAttribute->getAttribute();
            $attributeId = $attribute?->getId();
            if ($attribute === null || $attributeId === null) {
                continue;
            }

            $collected = [];
            foreach ($byUser as $userValues) {
                if (!\array_key_exists($attributeId, $userValues)) {
                    continue;
                }
                $value = $userValues[$attributeId];
                if (AttributeValueHelper::isEmpty($value)) {
                    continue;
                }
                $collected[] = $value;
            }

            $attributes[] = [
                'title' => $attribute->getName(),
                'type' => $attribute->getType(),
                'result' => $this->summarize($attribute->getType(), $collected),
            ];
        }

        return [
            'title' => $position->getTitle(),
            'publishedCvCount' => \count($published),
            'attributes' => $attributes,
        ];
    }

    /**
     * @param list<mixed> $values
     * @return array<string, mixed>
     */
    private function summarize(string $type, array $values): array
    {
        $resolved = AttributeType::tryFrom($type) ?? AttributeType::String;

        return match ($resolved) {
            AttributeType::Numeric => $this->summarizeNumeric($values),
            AttributeType::Boolean => $this->summarizeBoolean($values),
            AttributeType::Date => $this->summarizeDate($values),
            AttributeType::Period, AttributeType::Image => ['count' => \count($values)],
            default => $this->summarizeText($values),
        };
    }

    /**
     * @param list<mixed> $values
     * @return array{count: int, min: int|float|null, max: int|float|null, average: float|null}
     */
    private function summarizeNumeric(array $values): array
    {
        $numbers = [];
        foreach ($values as $value) {
            if (\is_int($value) || \is_float($value)) {
                $numbers[] = $value;
            }
        }

        if ($numbers === []) {
            return ['count' => 0, 'min' => null, 'max' => null, 'average' => null];
        }

        return [
            'count' => \count($numbers),
            'min' => min($numbers),
            'max' => max($numbers),
            'average' => round(array_sum($numbers) / \count($numbers), 2),
        ];
    }

    /**
     * @param list<mixed> $values
     * @return array{count: int, top: list<array{value: string, count: int}>}
     */
    private function summarizeText(array $values): array
    {
        $counts = [];
        foreach ($values as $value) {
            if (!\is_scalar($value)) {
                continue;
            }
            $text = (string) $value;
            $counts[$text] = ($counts[$text] ?? 0) + 1;
        }

        $top = [];
        foreach ($counts as $value => $count) {
            $top[] = ['value' => (string) $value, 'count' => $count];
        }

        usort($top, static function (array $left, array $right): int {
            $byCount = $right['count'] <=> $left['count'];
            if ($byCount !== 0) {
                return $byCount;
            }

            return $left['value'] <=> $right['value'];
        });

        return [
            'count' => array_sum($counts),
            'top' => \array_slice($top, 0, 5),
        ];
    }

    /**
     * @param list<mixed> $values
     * @return array{count: int, true: int, false: int}
     */
    private function summarizeBoolean(array $values): array
    {
        $true = 0;
        $false = 0;
        foreach ($values as $value) {
            if ($value === true) {
                ++$true;
            } elseif ($value === false) {
                ++$false;
            }
        }

        return ['count' => $true + $false, 'true' => $true, 'false' => $false];
    }

    /**
     * @param list<mixed> $values
     * @return array{count: int, earliest: string|null, latest: string|null}
     */
    private function summarizeDate(array $values): array
    {
        $dates = [];
        foreach ($values as $value) {
            if (\is_string($value) && $value !== '') {
                $dates[] = $value;
            }
        }

        if ($dates === []) {
            return ['count' => 0, 'earliest' => null, 'latest' => null];
        }

        sort($dates);

        return [
            'count' => \count($dates),
            'earliest' => $dates[0],
            'latest' => $dates[\count($dates) - 1],
        ];
    }
}
