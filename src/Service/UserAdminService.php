<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserAdminService
{
    public const ROLES = [
        User::ROLE_CANDIDATE,
        User::ROLE_RECRUITER,
        User::ROLE_ADMIN,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function setBlocked(User $user, User $actor, bool $blocked): void
    {
        $this->assertNotSelf($user, $actor);
        $user->setIsBlocked($blocked);
        $this->entityManager->flush();
    }

    public function addRole(User $user, string $role): void
    {
        $role = $this->requireRole($role);
        $roles = $user->getAssignedRoles();
        if (!\in_array($role, $roles, true)) {
            $roles[] = $role;
            $user->setRoles($roles);
            $this->entityManager->flush();
        }
    }

    public function removeRole(User $user, string $role): void
    {
        $role = $this->requireRole($role);
        $user->setRoles(array_values(array_filter(
            $user->getAssignedRoles(),
            static fn (string $assigned): bool => $assigned !== $role,
        )));
        $this->entityManager->flush();
    }

    public function delete(User $user, User $actor): void
    {
        $this->assertNotSelf($user, $actor);
        $user->getProjects()->toArray();
        $user->getDiscussionPosts()->toArray();
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function assertNotSelf(User $user, User $actor): void
    {
        if ($user->getId() !== null && $user->getId() === $actor->getId()) {
            throw new \InvalidArgumentException('user.flash.cannot_self');
        }
    }

    private function requireRole(string $role): string
    {
        if (!\in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException('user.flash.invalid_role');
        }

        return $role;
    }
}
