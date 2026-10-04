<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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

    /**
     * @param list<User> $users
     * @return array{applied: int, skippedSelf: bool, inUse: bool}
     */
    public function applyBlock(array $users, User $actor, bool $blocked): array
    {
        return $this->applySkippingSelf($users, function (User $user) use ($actor, $blocked): void {
            $this->setBlocked($user, $actor, $blocked);
        });
    }

    /**
     * @param list<User> $users
     * @return array{applied: int, skippedSelf: bool, inUse: bool}
     */
    public function applyRoles(array $users, string $role, bool $assign): array
    {
        foreach ($users as $user) {
            if ($assign) {
                $this->addRole($user, $role);
            } else {
                $this->removeRole($user, $role);
            }
        }

        return ['applied' => \count($users), 'skippedSelf' => false, 'inUse' => false];
    }

    /**
     * @param list<User> $users
     * @return array{applied: int, skippedSelf: bool, inUse: bool}
     */
    public function deleteSelected(array $users, User $actor): array
    {
        return $this->applySkippingSelf($users, function (User $user) use ($actor): void {
            $this->delete($user, $actor);
        });
    }

    /**
     * @param list<User> $users
     * @param callable(User): void $apply
     * @return array{applied: int, skippedSelf: bool, inUse: bool}
     */
    private function applySkippingSelf(array $users, callable $apply): array
    {
        $applied = 0;
        $skippedSelf = false;
        foreach ($users as $user) {
            try {
                $apply($user);
                ++$applied;
            } catch (\InvalidArgumentException $exception) {
                if ($exception->getMessage() === 'user.flash.cannot_self') {
                    $skippedSelf = true;
                    continue;
                }

                throw $exception;
            } catch (ForeignKeyConstraintViolationException) {
                return ['applied' => $applied, 'skippedSelf' => $skippedSelf, 'inUse' => true];
            }
        }

        return ['applied' => $applied, 'skippedSelf' => $skippedSelf, 'inUse' => false];
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
