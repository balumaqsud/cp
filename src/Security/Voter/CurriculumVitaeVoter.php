<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\CurriculumVitae;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Service\PositionAccessEvaluator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CurriculumVitaeVoter extends Voter
{
    public const VIEW = 'CV_VIEW';
    public const EDIT = 'CV_EDIT';
    public const PUBLISH = 'CV_PUBLISH';
    public const LIKE = 'CV_LIKE';
    public const LIST = 'CV_LIST';

    public function __construct(
        private readonly PositionAccessEvaluator $accessEvaluator,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::LIST) {
            return $subject === null;
        }

        return \in_array($attribute, [self::VIEW, self::EDIT, self::PUBLISH, self::LIKE], true)
            && $subject instanceof CurriculumVitae;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || $user->isBlocked()) {
            return false;
        }

        if ($attribute === self::LIST) {
            return $user->isRecruiter();
        }

        if (!$subject instanceof CurriculumVitae) {
            return false;
        }

        $owner = $subject->getUser();
        $position = $subject->getPosition();
        $isOwner = $owner !== null && $owner->getId() === $user->getId();

        if ($user->isAdmin()) {
            return true;
        }

        $ownerStillHasAccess = $owner !== null && $position !== null
            && $this->accessEvaluator->canAccess($owner, $position, false);

        return match ($attribute) {
            self::VIEW => $this->canView($user, $subject, $isOwner, $ownerStillHasAccess),
            self::EDIT, self::PUBLISH => $isOwner && $ownerStillHasAccess,
            self::LIKE => $user->isRecruiter()
                && $subject->getStatus() === CvStatus::Published->value
                && $ownerStillHasAccess,
            default => false,
        };
    }

    private function canView(User $user, CurriculumVitae $cv, bool $isOwner, bool $ownerStillHasAccess): bool
    {
        if ($isOwner) {
            return $ownerStillHasAccess;
        }

        return $user->isRecruiter()
            && $cv->getStatus() === CvStatus::Published->value
            && $ownerStillHasAccess;
    }
}
