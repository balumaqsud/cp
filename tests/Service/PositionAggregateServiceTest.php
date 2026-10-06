<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Attribute;
use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\PositionAttribute;
use App\Entity\User;
use App\Enum\AttributeType;
use App\Enum\CvStatus;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Service\PositionAggregateService;
use PHPUnit\Framework\TestCase;

final class PositionAggregateServiceTest extends TestCase
{
    public function testBuildUsesPublishedCvsOnly(): void
    {
        $gpa = $this->attribute(10, 'GPA', AttributeType::Numeric);
        $english = $this->attribute(11, 'English Level', AttributeType::Choice);
        $notes = $this->attribute(12, 'Notes', AttributeType::String);

        $position = new Position();
        $position->setTitle('Business Analyst');
        $position->addPositionAttribute($this->link($gpa));
        $position->addPositionAttribute($this->link($english));
        $position->addPositionAttribute($this->link($notes));

        $publishedUser = $this->user(1);
        $draftUser = $this->user(2);
        $published = $this->cv($publishedUser, $position, CvStatus::Published);
        $this->cv($draftUser, $position, CvStatus::Draft);

        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::once())
            ->method('findPublishedByPosition')
            ->with($position)
            ->willReturn([$published]);

        $values = $this->createMock(AttributeValueRepository::class);
        $values->expects(self::once())
            ->method('findIndexedForUserIds')
            ->with([1])
            ->willReturn([
                1 => [
                    10 => 3.456,
                    11 => 'Advanced',
                ],
            ]);

        $summary = (new PositionAggregateService($cvs, $values))->build($position);

        self::assertSame('Business Analyst', $summary['title']);
        self::assertSame(1, $summary['publishedCvCount']);
        self::assertSame([
            [
                'title' => 'GPA',
                'type' => 'numeric',
                'result' => ['count' => 1, 'min' => 3.456, 'max' => 3.456, 'average' => 3.46],
            ],
            [
                'title' => 'English Level',
                'type' => 'choice',
                'result' => ['count' => 1, 'top' => [['value' => 'Advanced', 'count' => 1]]],
            ],
            [
                'title' => 'Notes',
                'type' => 'string',
                'result' => ['count' => 0, 'top' => []],
            ],
        ], $summary['attributes']);
    }

    private function link(Attribute $attribute): PositionAttribute
    {
        $link = new PositionAttribute();
        $link->setAttribute($attribute);

        return $link;
    }

    private function cv(User $owner, Position $position, CvStatus $status): CurriculumVitae
    {
        $cv = new CurriculumVitae();
        $cv->setUser($owner);
        $cv->setPosition($position);
        $cv->setStatus($status->value);

        return $cv;
    }

    private function attribute(int $id, string $name, AttributeType $type): Attribute
    {
        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setType($type->value);

        $property = new \ReflectionProperty($attribute, 'id');
        $property->setValue($attribute, $id);

        return $attribute;
    }

    private function user(int $id): User
    {
        $user = new User();
        $user->setEmail('user'.$id.'@example.test');
        $user->setName('User '.$id);

        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);

        return $user;
    }
}
