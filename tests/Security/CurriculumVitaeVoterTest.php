<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Attribute;
use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\User;
use App\Enum\AccessOperator;
use App\Enum\AttributeType;
use App\Enum\CvStatus;
use App\Repository\AttributeValueRepository;
use App\Security\Voter\CurriculumVitaeVoter;
use App\Service\PositionAccessEvaluator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class CurriculumVitaeVoterTest extends TestCase
{
    public function testOwnerAndRecruiterCannotViewAfterAccessIsLost(): void
    {
        $owner = $this->user(5, [User::ROLE_CANDIDATE]);
        $cv = $this->publishedCv($owner, $this->restrictedPosition());
        $voter = $this->voter([]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($owner), $cv, [CurriculumVitaeVoter::VIEW]));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($this->user(2, [User::ROLE_RECRUITER])), $cv, [CurriculumVitaeVoter::VIEW]),
        );
    }

    public function testAdminCanViewAfterTheOwnerLosesAccess(): void
    {
        $cv = $this->publishedCv($this->user(5, [User::ROLE_CANDIDATE]), $this->restrictedPosition());
        $vote = $this->voter([])->vote(
            $this->token($this->user(1, [User::ROLE_ADMIN])),
            $cv,
            [CurriculumVitaeVoter::VIEW],
        );

        self::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testOnlyARecruiterCanLikeAPublishedVisibleCv(): void
    {
        $cv = $this->publishedCv($this->user(5, [User::ROLE_CANDIDATE]), new Position());
        $voter = $this->voter([]);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($this->user(2, [User::ROLE_RECRUITER])), $cv, [CurriculumVitaeVoter::LIKE]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($this->user(4, [User::ROLE_CANDIDATE])), $cv, [CurriculumVitaeVoter::LIKE]),
        );
    }

    public function testDraftCannotBeLiked(): void
    {
        $cv = $this->publishedCv($this->user(5, [User::ROLE_CANDIDATE]), new Position());
        $cv->setStatus(CvStatus::Draft->value);

        $vote = $this->voter([])->vote(
            $this->token($this->user(2, [User::ROLE_RECRUITER])),
            $cv,
            [CurriculumVitaeVoter::LIKE],
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    /**
     * @param array<int, mixed> $values
     */
    private function voter(array $values): CurriculumVitaeVoter
    {
        $repository = $this->createStub(AttributeValueRepository::class);
        $repository->method('findIndexedByAttributeId')->willReturn($values);

        return new CurriculumVitaeVoter(new PositionAccessEvaluator($repository));
    }

    private function publishedCv(User $owner, Position $position): CurriculumVitae
    {
        $cv = new CurriculumVitae();
        $cv->setUser($owner);
        $cv->setPosition($position);
        $cv->setStatus(CvStatus::Published->value);

        return $cv;
    }

    private function restrictedPosition(): Position
    {
        $attribute = new Attribute();
        $attribute->setName('Years');
        $attribute->setType(AttributeType::Numeric->value);
        $property = new \ReflectionProperty($attribute, 'id');
        $property->setValue($attribute, 1);

        $position = new Position();
        $position->setIsPublic(false);
        $rule = new PositionAccessRule();
        $rule->setAttribute($attribute);
        $rule->setOperator(AccessOperator::Gte->value);
        $rule->setCompareValue(3);
        $position->addAccessRule($rule);

        return $position;
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
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
