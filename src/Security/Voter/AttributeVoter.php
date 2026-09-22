<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AttributeVoter extends Voter
{
    public const VIEW = 'ATTRIBUTE_VIEW';
    public const MANAGE = 'ATTRIBUTE_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || $user->isBlocked()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $user->hasAssignedRole(),
            self::MANAGE => $user->isRecruiter(),
            default => false,
        };
    }
}
