<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Position;
use App\Entity\User;
use App\Service\PositionAccessEvaluator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PositionVoter extends Voter
{
    public const VIEW = 'POSITION_VIEW';
    public const MANAGE = 'POSITION_MANAGE';
    public const GENERATE_CV = 'POSITION_GENERATE_CV';
    public const DISCUSS = 'POSITION_DISCUSS';

    public function __construct(
        private readonly PositionAccessEvaluator $accessEvaluator,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE, self::GENERATE_CV, self::DISCUSS], true)
            && ($subject instanceof Position || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($attribute === self::MANAGE) {
            return $user instanceof User && !$user->isBlocked() && $user->isRecruiter();
        }

        if (!$subject instanceof Position) {
            return false;
        }

        if ($attribute === self::VIEW) {
            if ($subject->isPublic()) {
                return true;
            }

            return $user instanceof User && !$user->isBlocked() && $this->accessEvaluator->canAccess($user, $subject, true);
        }

        if (!$user instanceof User || $user->isBlocked()) {
            return false;
        }

        return match ($attribute) {
            self::DISCUSS => $this->accessEvaluator->canAccess($user, $subject, true),
            self::GENERATE_CV => $user->isCandidate() && $this->accessEvaluator->canAccess($user, $subject, false),
            default => false,
        };
    }
}
