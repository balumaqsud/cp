<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Attribute;
use App\Entity\CurriculumVitae;
use App\Entity\CvLike;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\PositionAttribute;
use App\Entity\User;
use App\Enum\AccessOperator;
use App\Enum\AttributeType;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\ProjectRepository;
use App\Service\CvService;
use App\Service\PositionAccessEvaluator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CvServiceTest extends TestCase
{
    public function testPublishRejectsAnIncompleteCv(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $attributes = $this->createStub(AttributeRepository::class);
        $attributes->method('findBuiltIns')->willReturn([$this->attribute(1)]);

        $values = $this->createStub(AttributeValueRepository::class);
        $values->method('findIndexedByAttributeId')->willReturn([]);

        $cv = $this->cv($this->user(4, [User::ROLE_CANDIDATE]), new Position());

        $this->expectException(\InvalidArgumentException::class);
        $this->service($entityManager, $attributes, $values)->publish($cv);
    }

    public function testPublishSetsPublishedWhenRequiredValuesAreFilled(): void
    {
        $builtIn = $this->attribute(1);
        $skill = $this->attribute(2);
        $position = new Position();
        $requirement = new PositionAttribute();
        $requirement->setAttribute($skill);
        $requirement->setIsRequired(true);
        $position->addPositionAttribute($requirement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $attributes = $this->createStub(AttributeRepository::class);
        $attributes->method('findBuiltIns')->willReturn([$builtIn]);

        $values = $this->createStub(AttributeValueRepository::class);
        $values->method('findIndexedByAttributeId')->willReturn([1 => 'Ada', 2 => 'C1']);

        $cv = $this->cv($this->user(4, [User::ROLE_CANDIDATE]), $position);
        $this->service($entityManager, $attributes, $values)->publish($cv);

        self::assertSame(CvStatus::Published->value, $cv->getStatus());
    }

    public function testPublishedCvIsHiddenWhenTheOwnerLosesAccess(): void
    {
        $owner = $this->user(5, [User::ROLE_CANDIDATE]);
        $cv = $this->cv($owner, $this->restrictedPosition($this->attribute(1)));
        $cv->setStatus(CvStatus::Published->value);

        $cvs = $this->createStub(CurriculumVitaeRepository::class);
        $cvs->method('findPublishedForRecruiting')->willReturn([$cv]);

        $values = $this->createStub(AttributeValueRepository::class);
        $values->method('findIndexedForUserIds')->willReturn([5 => [1 => 1]]);

        $service = $this->service(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(AttributeRepository::class),
            $values,
            $cvs,
        );

        self::assertSame([], $service->publishedVisibleTo($this->user(2, [User::ROLE_RECRUITER])));
        self::assertSame([$cv], $service->publishedVisibleTo($this->user(1, [User::ROLE_ADMIN])));
    }

    public function testToggleLikeRemovesAnExistingLike(): void
    {
        $existing = new CvLike();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($existing);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $likes = $this->createStub(CvLikeRepository::class);
        $likes->method('findOneByRecruiterAndCv')->willReturn($existing);

        $removed = $this->service(
            $entityManager,
            $this->createStub(AttributeRepository::class),
            $this->createStub(AttributeValueRepository::class),
            likes: $likes,
        )->toggleLike($this->user(2, [User::ROLE_RECRUITER]), new CurriculumVitae());

        self::assertFalse($removed);
    }

    public function testToggleLikeStoresOneLikeWhenNoneExists(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(CvLike::class));
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::once())->method('flush');

        $likes = $this->createStub(CvLikeRepository::class);
        $likes->method('findOneByRecruiterAndCv')->willReturn(null);

        $added = $this->service(
            $entityManager,
            $this->createStub(AttributeRepository::class),
            $this->createStub(AttributeValueRepository::class),
            likes: $likes,
        )->toggleLike($this->user(2, [User::ROLE_RECRUITER]), new CurriculumVitae());

        self::assertTrue($added);
    }

    private function service(
        EntityManagerInterface $entityManager,
        AttributeRepository $attributes,
        AttributeValueRepository $values,
        ?CurriculumVitaeRepository $cvs = null,
        ?CvLikeRepository $likes = null,
    ): CvService {
        return new CvService(
            $cvs ?? $this->createStub(CurriculumVitaeRepository::class),
            $this->createStub(ProjectRepository::class),
            $attributes,
            $values,
            $likes ?? $this->createStub(CvLikeRepository::class),
            new PositionAccessEvaluator($this->createStub(AttributeValueRepository::class)),
            $entityManager,
        );
    }

    private function cv(User $owner, Position $position): CurriculumVitae
    {
        $cv = new CurriculumVitae();
        $cv->setUser($owner);
        $cv->setPosition($position);

        return $cv;
    }

    private function restrictedPosition(Attribute $attribute): Position
    {
        $position = new Position();
        $position->setIsPublic(false);

        $rule = new PositionAccessRule();
        $rule->setAttribute($attribute);
        $rule->setOperator(AccessOperator::Gte->value);
        $rule->setCompareValue(3);
        $position->addAccessRule($rule);

        return $position;
    }

    private function attribute(int $id): Attribute
    {
        $attribute = new Attribute();
        $attribute->setName('Attribute '.$id);
        $attribute->setType(AttributeType::Numeric->value);

        $property = new \ReflectionProperty($attribute, 'id');
        $property->setValue($attribute, $id);

        return $attribute;
    }

    /**
     * @param list<string> $roles
     */
    private function user(int $id, array $roles): User
    {
        $user = new User();
        $user->setEmail('user'.$id.'@example.test');
        $user->setName('User '.$id);
        $user->setRoles($roles);

        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);

        return $user;
    }
}
