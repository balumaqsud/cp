<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\LoginDTO;
use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Exception\AuthException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function register(RegistrationDTO $registrationDTO): User
    {
        $email = mb_strtolower(trim($registrationDTO->email));
        if ($this->users->findOneByEmail($email) !== null) {
            throw new AuthException('auth.flash.exists');
        }

        $user = new User();
        $user->setName(trim($registrationDTO->name));
        $user->setEmail($email);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $registrationDTO->password));
        $user->setRoles([$registrationDTO->role]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function login(LoginDTO $loginDTO): User
    {
        $email = mb_strtolower(trim($loginDTO->email));
        $user = $this->users->findOneByEmail($email);
        if ($user === null || $user->getPassword() === null) {
            throw new AuthException('auth.flash.invalid');
        }

        if (!$this->userPasswordHasher->isPasswordValid($user, $loginDTO->password)) {
            throw new AuthException('auth.flash.invalid');
        }

        if ($user->isBlocked()) {
            throw new AuthException('auth.flash.blocked');
        }

        return $user;
    }
}
