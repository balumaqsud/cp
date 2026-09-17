<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\DTO\RegistrationDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,

    ) {}

    public function register(RegistrationDTO $registrationDTO): User
    {
        $email = mb_strtolower(trim($registrationDTO->email));
        $existingUser = $this->users->findOneBy(['email' => $email]);
        if ($existingUser) {
            throw new \Exception('User with this email already exists.');
        }
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $registrationDTO->password));
        $user->setRoles([$registrationDTO->role]);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
}
