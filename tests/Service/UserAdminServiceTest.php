<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserAdminService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserAdminServiceTest extends TestCase
{
    public function testCannotBlockSelf(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $service = new UserAdminService($entityManager);
        $user = $this->user(1, [User::ROLE_ADMIN]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('user.flash.cannot_self');
        $service->setBlocked($user, $user, true);
    }

    public function testCannotDeleteSelf(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');

        $service = new UserAdminService($entityManager);
        $user = $this->user(1, [User::ROLE_ADMIN]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('user.flash.cannot_self');
        $service->delete($user, $user);
    }

    public function testAdminMayRemoveOwnAdministratorRole(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new UserAdminService($entityManager);
        $user = $this->user(1, [User::ROLE_ADMIN, User::ROLE_RECRUITER]);

        $service->removeRole($user, User::ROLE_ADMIN);

        self::assertSame([User::ROLE_RECRUITER], $user->getAssignedRoles());
        self::assertFalse($user->isAdmin());
    }

    /**
     * @param list<string> $roles
     */
    private function user(int $id, array $roles): User
    {
        $user = new User();
        $user->setEmail('admin'.$id.'@example.test');
        $user->setName('Admin');
        $user->setRoles($roles);

        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);

        return $user;
    }
}
