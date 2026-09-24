<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Attribute;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Enum\AccessOperator;
use App\Enum\AttributeType;
use App\Repository\AttributeValueRepository;
use App\Service\PositionAccessEvaluator;
use PHPUnit\Framework\TestCase;

final class PositionAccessEvaluatorTest extends TestCase
{
    public function testNumericGreaterOrEqual(): void
    {
        $position = $this->restrictedPosition(
            $this->attribute(1, AttributeType::Numeric),
            AccessOperator::Gte,
            7,
        );

        $evaluator = $this->evaluator();

        self::assertTrue($evaluator->matchesValues($position, [1 => 7.5]));
        self::assertTrue($evaluator->matchesValues($position, [1 => 7]));
        self::assertFalse($evaluator->matchesValues($position, [1 => 6.9]));
    }

    public function testBooleanEquals(): void
    {
        $position = $this->restrictedPosition(
            $this->attribute(2, AttributeType::Boolean),
            AccessOperator::Eq,
            true,
        );

        $evaluator = $this->evaluator();

        self::assertTrue($evaluator->matchesValues($position, [2 => true]));
        self::assertFalse($evaluator->matchesValues($position, [2 => false]));
    }

    public function testStringContains(): void
    {
        $position = $this->restrictedPosition(
            $this->attribute(3, AttributeType::String),
            AccessOperator::Contains,
            'english',
        );

        $evaluator = $this->evaluator();

        self::assertTrue($evaluator->matchesValues($position, [3 => 'Advanced English']));
        self::assertFalse($evaluator->matchesValues($position, [3 => 'German']));
    }

    private function evaluator(): PositionAccessEvaluator
    {
        return new PositionAccessEvaluator(
            $this->createStub(AttributeValueRepository::class),
        );
    }

    private function restrictedPosition(Attribute $attribute, AccessOperator $operator, mixed $expected): Position
    {
        $position = new Position();
        $position->setIsPublic(false);

        $rule = new PositionAccessRule();
        $rule->setAttribute($attribute);
        $rule->setOperator($operator->value);
        $rule->setCompareValue($expected);
        $position->addAccessRule($rule);

        return $position;
    }

    private function attribute(int $id, AttributeType $type): Attribute
    {
        $attribute = new Attribute();
        $attribute->setName('attr-'.$id);
        $attribute->setType($type->value);

        $property = new \ReflectionProperty($attribute, 'id');
        $property->setValue($attribute, $id);

        return $attribute;
    }
}
