<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProfileVoter extends Voter
{
    public const VIEW = 'PROFILE_VIEW';
    public const EDIT = 'PROFILE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || $user->isBlocked() || !$subject instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->getId() === $subject->getId();
    }
}
