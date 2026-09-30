<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\User;
use App\Enum\AccessOperator;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\PositionRepository;
use App\Repository\ProjectRepository;
use App\Service\CvService;
use App\Service\PositionAccessEvaluator;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testGuestSeesPublicPositionsOnly(): void
    {
        $public = $this->position(true);
        $restricted = $this->position(false);
        $positions = $this->createStub(PositionRepository::class);
        $positions->method('searchFullText')->willReturn([$public, $restricted]);

        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::never())->method('searchPublishedFullText');

        $result = $this->search($positions, $cvs)->search('php', null);

        self::assertSame([$public], $result['positions']);
        self::assertSame([], $result['cvs']);
    }

    public function testCandidateSeesPositionsOnly(): void
    {
        $public = $this->position(true);
        $restricted = $this->position(false);
        $positions = $this->createStub(PositionRepository::class);
        $positions->method('searchFullText')->willReturn([$public, $restricted]);

        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::never())->method('searchPublishedFullText');

        $result = $this->search($positions, $cvs)->search('php', $this->user(4, [User::ROLE_CANDIDATE]));

        self::assertSame([$public], $result['positions']);
        self::assertSame([], $result['cvs']);
    }

    public function testRecruiterReceivesPublishedCvsThatRemainVisible(): void
    {
        $public = $this->position(true);
        $restricted = $this->position(false);
        $visible = $this->cv($this->user(5, [User::ROLE_CANDIDATE]), $public);
        $hidden = $this->cv($this->user(6, [User::ROLE_CANDIDATE]), $restricted);

        $positions = $this->createStub(PositionRepository::class);
        $positions->method('searchFullText')->willReturn([$public, $restricted]);

        $cvs = $this->createStub(CurriculumVitaeRepository::class);
        $cvs->method('searchPublishedFullText')->willReturn([$visible, $hidden]);

        $values = $this->createStub(AttributeValueRepository::class);
        $values->method('findIndexedForUserIds')->willReturn([5 => [], 6 => []]);

        $result = $this->search($positions, $cvs, $values)->search('php', $this->user(2, [User::ROLE_RECRUITER]));

        self::assertSame([$public, $restricted], $result['positions']);
        self::assertSame([$visible], $result['cvs']);
    }

    private function search(
        PositionRepository $positions,
        CurriculumVitaeRepository $cvs,
        ?AttributeValueRepository $values = null,
    ): SearchService {
        $values ??= $this->createStub(AttributeValueRepository::class);
        $evaluator = new PositionAccessEvaluator($this->createStub(AttributeValueRepository::class));

        return new SearchService(
            $positions,
            $cvs,
            $evaluator,
            new CvService(
                $this->createStub(CurriculumVitaeRepository::class),
                $this->createStub(ProjectRepository::class),
                $this->createStub(AttributeRepository::class),
                $values,
                $this->createStub(CvLikeRepository::class),
                $evaluator,
                $this->createStub(EntityManagerInterface::class),
            ),
        );
    }

    private function position(bool $public): Position
    {
        $position = new Position();
        $position->setTitle($public ? 'Junior' : 'Senior');
        $position->setIsPublic($public);
        if (!$public) {
            $rule = new PositionAccessRule();
            $rule->setOperator(AccessOperator::Gte->value);
            $rule->setCompareValue(3);
            $position->addAccessRule($rule);
        }

        return $position;
    }

    private function cv(User $owner, Position $position): CurriculumVitae
    {
        $cv = new CurriculumVitae();
        $cv->setUser($owner);
        $cv->setPosition($position);
        $cv->setStatus(CvStatus::Published->value);

        return $cv;
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
